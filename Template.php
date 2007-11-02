<?php
require('Filters.php');
class Template
{
    
    private $buffer;    
    private $meta;
    public $vars;
    private $guard = "free";
    
    function __construct($template)
    {
        require ("_droneSettings.php");
        if ($debugMode)
            $this->startTime = microtime();
        if ($template{0}=="?")
        {   
            if (file_exists(substr($template,1)))
                $filename = substr($template,1);
            else
            if (isset($templateDir) && file_exists($templateDir.substr($template,1)))
                $filename = $templateDir.substr($template,1);
            else
                if (file_exists("templates/".substr($template,1)))
                    $filename = "templates/".substr($template,1);
                else
                    $filename = "phpDrone/templates/".substr($template,1);
        }
        else
            if (file_exists($template))
                $filename = $template;
            else
            if (isset($templateDir))
                $filename = $templateDir.$template;
            else
                if (file_exists("templates/".$template))
                    $filename = "templates/".$template;
                else
                    die("phpDrone error: Template file <b>templates/{$template}</b> was not be found.");
                
                
        $this->template = "";
        $this->buildTemplate($filename);                
        $this->vars = array();
    }

    function buildTemplate($templateFile)
    {
        $this->solveInheritance($templateFile);
        //clear the block-related tags
        $this->template = preg_replace('/{%block .*%}|{%end-block%}/',"",$this->template);
    }

    function solveInheritance($templateFile)
    {        
        $handle = fopen($templateFile, "r");
        $templateContent = fread($handle, filesize($templateFile));
        fclose($handle);

        //see if the templates extends another and if so, recursivly call the solveInheritance
        if (preg_match('/{%(?:\\s|)extends (?P<baseTemplate>.*)(?:\\s|)%}/', $templateContent, $result))
        {
            $baseTemplate = $result['baseTemplate'];
            $this->solveInheritance(dirname($templateFile)."/".$baseTemplate);

            //get the names of all the blocks in the template.
            preg_match_all('/\\{%(?:\s|)block (?P<blocName>[\\w]*)(?:\s|)%\\}/', $templateContent, $blocks);
            foreach($blocks['blocName'] as $item)
            {
                $item = trim($item);
                //get the content of the found block
                //note to self: ATENTIE!!!!! AM PUS AICI UN "?" DUPA ".*" FARA SA VERIFIC DACA MERE
                preg_match('/(?:\\{%(?:\s|)block '.$item.'(?:\s|)%\\})(?P<blockContent>.*?)\\{%(?:\s|)end-block(?:\s|)%\\}/s', $templateContent, $blocksContent);
                //replace the block content in the base template with the one from the child template
                $this->template = preg_replace ('/(.*){%(?:\s|)block '.$item.'(?:\s|)%}.*?{%(?:\s|)end-block(?:\s|)%}(.*)/s','\\1{%block '.$item.'%}'.$blocksContent['blockContent'].'{%end-block%}\\2',$this->template);
            }
        }
        else
            $this->template = $templateContent;
    }
    
    function addMeta($meta)
    {
        $this->writeVar("meta",$meta."\n");
    }

    function setGuard($guard)
    {
        $this->guard = $guard;
    }

    private function deltaTime()
    {
        $time = microtime();
        return $time-$this->startTime;
    }

    private function solveVar($input,$php_vars)
    {
        $output = $input;
        preg_match_all('/{%(?P<cont>[^\\}]*)%}/',$output,$vals);
        foreach($vals['cont'] as $f_val)
        {
            $pieces = preg_split("/\|/",trim($f_val));
            $subs = preg_split("/\./",trim($pieces[0]));
            if (count($subs)==1)
            {
                $ev = "\$php_vars['".$subs[0]."']";
                eval("\$val=$ev;");
            }
            else
            {
                $ev = "\$php_vars";
                foreach ($subs as $item)
                    $ev.= "['".$item."']";
                eval("\$val=$ev;");
            }
            
            //apply filters
            if (count($pieces)>1)
                for ($f=1;$f<count($pieces);$f++)
                {
                    if (preg_match('/(?P<filterName>.*)\\((?P<filterArgs>.+)\\)/',$pieces[$f],$capt))
                    {
                        $filterName = $capt['filterName'];
                        $filterArgs = ",".$capt['filterArgs'];
                    }
                    else
                    {
                        $filterName = str_replace(array("(",")"),"",$pieces[$f]);
                        $filterArgs = "";
                    }
                    if (function_exists("filter_".$filterName))
                        eval('$val=filter_'.$filterName.'("'.$val.'"'.$filterArgs.');');
                    else
                        die("phpDrone error: Unknown filter: <b>{$filterName}</b>.");
                }
            
            $output = preg_replace ('/{%(?:[ ]*|)'.addcslashes(addslashes($f_val),"|(.)").'(?:[ ]*|)%}/',$val,$output);
        }
        return $output;
    }

    private function solveIf($input,$php_vars)
    {
        $output = $input;
        preg_match_all('/{%([ ]*|)if([\\d]*|) (?P<ifStatement>[^\\}]*)%}/', $output, $ifs);
        foreach($ifs['ifStatement'] as $ifStatement)
        {
            $toEval = trim($ifStatement);
            $parts = preg_split('/(?:==)|(?:<=)|(?:>=)|(?:<)|(?:>)|(?:\|\|)|(?:&&)|(?:\()|(?:\))/',$toEval);
            if (count($parts)!=1)
            {
                foreach ($parts as $part)
                {
                    $val_parts = preg_split("/\./",trim($part));
                    if (count($val_parts)==1)
                    {
                        $ev = "\$php_vars['".$val_parts[0]."']";
                    }
                    else
                    {
                        $ev = "\$php_vars";
                        foreach ($val_parts as $item)
                            $ev.= "['".$item."']";
                    }
                    eval("\$isSet = isset($ev);");
                    eval("\$val =$ev;");
                    eval("\$type = gettype($ev);");


                    if ($isSet)
                    {
                        if ($type=="string" && $val!="")
                            $val = "'".$val."'";
                        $toEval = preg_replace('/'.addslashes($part).'/',$val,$toEval);
                        if ($toEval=="")
                            $toEval = "False";
                    }
                }
            }
            else
            {
                $val_parts = preg_split("/\./",trim($toEval));
                if (count($val_parts)==1)
                {
                    if (isset($php_vars[$toEval]))
                    {
                        $toEval = '"'.$php_vars[$toEval].'"';
                        if ($toEval=="")
                            $toEval = "False";
                    }
                    else
                        $toEval = "False";
                }
                else
                {
                    $toEval = "\$php_vars";
                    foreach ($val_parts as $item)
                        $toEval.= "['".$item."']";
                }
            }
            eval("\$result=$toEval;");
            
            if (!$result)
            {
                //{%(?:[ ]*|)if section%}(?P<ifBlock>[^\x00]*?)(?:(?:{%(?:[ ]*|)else(?:[ ]*|)%})(?P<elseBlock>[^\x00]*?))?{%(?:[ ]*|)end-if(?:[ ]*|)%}
                preg_match('/{%(?:[ ]*|)if([\\d]*|) '.$ifStatement.'%}(?P<ifBlock>[^\\x00]*?)(?:(?:{%(?:[ ]*|)else(?:[ ]*|)%})(?P<elseBlock>[^\\x00]*?))?{%(?:[ ]*|)end-if\\1(?:[ ]*|)%}/',$output,$capt);
                $output = preg_replace ('/{%(?:[ ]*|)if([\\d]*|) '.$ifStatement.'%}(?:[^\\x00]*?){%(?:[ ]*|)end-if\\1(?:[ ]*|)%}/',$capt['elseBlock'],$output,1);
            }
            else
            {
                preg_match('/{%(?:[ ]*|)if([\\d]*|) '.$ifStatement.'%}(?P<ifCont>[^\\x00]*?){%(?:[ ]*|)end-if\\1(?:[ ]*|)%}/',$output,$capt);
                $ifContent = $capt['ifCont'];
                $ifContent = preg_replace('/(?:(?:{%(?:[ ]*|)else(?:[ ]*|)%})(?P<elseBlock>[^\\\\x00]*?))[^\\x00]*/','',$ifContent);
                $output = preg_replace ('/{%(?:[ ]*|)if([\\d]*|) '.$ifStatement.'%}(?:[^\\x00]*?){%(?:[ ]*|)end-if\\1(?:[ ]*|)%}/',$ifContent,$output,1);
            }
        }
        return $output;
    }

    private function solveFor($input,$php_vars)
    {
        $output = $input;
        preg_match_all('/{%(?:[ ]|)for([\\d]*|) (?P<item>.*) in (?P<bunch>.*?)%}/', $output, $fors);
        $pas = 0;
        foreach($fors['bunch'] as $bunch)
        {
            $item = $fors['item'][$pas];
            //{%(?:[ ]|)for([\d]*|) (?:.*?) in (?:.*?)%}(?:\n){0,1}(?P<forblock>[^\x00]*?)(?:\n){0,1}{%end-for\1%} - get the block
            // get the if block content
            preg_match('/{%(?:[ ]|)for([\\d]*|) '.$item.' in '.$bunch.'%}(?:\\n){0,1}(?P<forblock>[^\\x00]*?)(?:\\n){0,1}{%end-for\\1%}/', $output, $forBlocksContent);
            $blockContent = $forBlocksContent['forblock'];

            if (isset($php_vars[trim($bunch)]) || is_numeric(trim($bunch)))
            {
                if (is_numeric(trim($bunch)))
                    $type = "integer";
                else
                    $type = gettype($php_vars[trim($bunch)]);
                if ($type=="array" || $type=="object" || $type=="string" || $type=="integer")
                {
                    if ($type=="integer")
                    {
                        $pacient = array();
                        if (is_numeric(trim($bunch)))
                            $upLimit = trim($bunch);
                        else
                            $upLimit =$php_vars[trim($bunch)];
                        for ($f=0;$f<=$upLimit;$f++)
                            $pacient[$f] = $f;
                    }
                    else if ($type=="string")
                        $pacient = preg_split('//', $php_vars[trim($bunch)], -1, PREG_SPLIT_NO_EMPTY);
                    else
                        $pacient = $php_vars[trim($bunch)];

                    $newContent = "";
                    $pas_2 = 0;
                    foreach ($pacient as $value)
                    {
                        if (gettype($value)=="array" || gettype($value)=="object")
                        {
                            preg_match_all('/{%(?:[ ]*|)'.$item.'.(?P<key>[^\\}|[\\ ]*)(?:[ ]*|)%}/',$blockContent,$keys);
                            $builtBlock = $blockContent;
                            foreach($keys['key'] as $f_key)
                                $builtBlock = preg_replace('/{%(?:[ ]*|)'.$item.'.'.$f_key.'(?:[ ]*|)%}/',$value[$f_key],$builtBlock);
                        }
                        else
                            $builtBlock = preg_replace('/{%(?:[ ]*|)'.$item.'(?:[ ]*|)%}/',$value,$blockContent);
                        $this->vars[$item] = $value;

                        // process cycles
                        preg_match_all('/{%(?:[ ]*|)cycle (?P<elems>.*?)(?:[ ]*|)%}/', $builtBlock, $capt);
                        foreach ($capt['elems'] as $i_item)
                        {
                            $parts = preg_split('/\,/',$i_item);
                            $builtBlock = preg_replace('/{%(?:[ ]*|)cycle '.$i_item.'(?:[ ]*|)%}/',$parts[$pas_2%count($parts)],$builtBlock);
                        }
                        $builtBlock = $this->compileTemplate($builtBlock,$this->vars);
                        $newContent .= $builtBlock;
                        $pas_2 ++;
                    }
//                     $blockContent = preg_replace('/([\\\\<{%}>*\/])/','\\\\\1',$blockContent);
                    //$this->template = preg_replace('/{%(?:[ ]*|)for '.$item.' in '.$bunch.'%}'.$blockContent.'{%end-for%}/',$newContent,$this->template);
                    $output = preg_replace('/{%(?:[ ]*|)for([\\d]*|) '.$item.' in '.$bunch.'%}([^\\x00]*?){%end-for\\1%}/',$newContent,$output,1);
                }
            }
            else
                $output = preg_replace ('/{%(?:[ ]|)for([\\d]*|) '.$item.' in '.$bunch.'%}(?:[\\s]*|.*)*{%end-for\\1%}/','',$output,1);
            $pas++;
        }
        return $output;
    }
    
    function compileTemplate($input,$phpVars)
    {
        $output = $input;
        $output = $this->solveFor($output,$phpVars);
        $output = $this->solveIf($output,$phpVars);
        $output = $this->solveVar($output,$phpVars);
        //delete the rest of unused vars from template
        $output = preg_replace ('/{%[^\\}]*%}/',"",$output);
        return $output;
    }

    function getBuffer()
    {
        require ("_droneSettings.php");
        $output = $this->compileTemplate($this->template,$this->vars);
        //take out reminders
        $output = preg_replace('/{%(?:[ ]*|)rem(?:[ ]*|)%}(?:[^\\x00]*){%(?:[ ]*|)end-rem(?:[ ]*|)%}/', '', $output);
        if (isset($compressHTML) && $compressHTML)
        {
            $output = preg_replace('/\n|\r\n|\t/', '', $output);
            $output = preg_replace('/[\s]{2,}/', ' ', $output);
        }
        return $output;
    }

    private function render_p($args)
    {
        require ("_droneSettings.php");
        if ($this->guard!="free")
            require ($this->guard);
        if ($this->guard=="free" || ($this->guard!="free") && __guard__())
        {
            $output = $this->getBuffer();
            if ($debugMode)
            {
                require("ver.php");
                $codeSize = sprintf("%.2f", strlen($output)/1024);
                $output .= "<!--This will apear only in debug mode -->\n<div id='droneDebugArea' style='font-size:0.8em;width:100%;border-top:1px solid silver;padding-left:4px;'><b>".$codeSize."</b> kb built in <b>".$this->deltaTime()."</b> seconds.<br />___________<br /><b>phpDrone</b> v{$phpDroneVersion}</div>";
            }
            print $output;
        }
        else
        {
            if (isset($guardFailPage))
                $guarFailPage = new Template($guardFailPage);
            else
                $guarFailPage = new Template("phpDrone/templates/gurd-failure.tmpl");
            $guarFailPage->write("title","Unauthorized - phpDrone");
            $guarFailPage->render();
        }
    }

    private function write_p($args)
    {
        if (count($args)>1)
            $this->vars[$args[0]] = $args[1];
        else
            if (count($args)==1)
                $this->vars["body"] .= $args[0];
            else
                die('phpDrone error: Function <b>Template->write()</b> takes at least one argument.');
    }

    private function __call($method, $args)
    {
        
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            //this wil be replaced later with a nicer error
            die("phpDrone error: Call to undefined method Template->".$method."()");
    }
}

?>
