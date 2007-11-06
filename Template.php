<?php
require_once('Filters.php');

if (file_exists('drone/Filters.php'))
{
    include('drone/Filters.php');
}

class Template
{
    
    private $buffer;    
    private $meta;
    public $vars;
    private $guard = "free";
    
    function __construct($template)
    {
        global $debugMode;
        if ($debugMode)
            $this->startTime = Utils::microTime();
        if ($template{0}=="?")
        {
            $droneDir = dirname(__FILE__);
            if (file_exists(substr($template,1)))
                $filename = substr($template,1);
            else
            if (isset($templateDir) && file_exists($templateDir.substr($template,1)))
                $filename = $templateDir.substr($template,1);
            else
                if (file_exists("templates/".substr($template,1)))
                    $filename = "templates/".substr($template,1);
                else
                    $filename = "{$droneDir}/templates/".substr($template,1);
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
                    Utils::throwDroneError(_("Template file was not be found").": <b>templates/{$template}</b>");
                
        $this->template = "";
        $this->buildTemplate($filename);                
        $this->vars = array();
    }
    
    function buildTemplate($templateFile)
    {
        $this->solveInheritance($templateFile);
        //clear the block-related tags
        $this->template = preg_replace('/{%block([\\d]*|) .*?%}|{%end-block([\\d]*|)%}/',"",$this->template);
    }

    function solveInheritance($templateFile)
    {
        if (!file_exists($templateFile))
            Utils::throwDroneError(_("Template file needed to extend other template was not found").": <b>{$templateFile}</b>");
        $handle = fopen($templateFile, "r");
        $templateContent = fread($handle, filesize($templateFile));
        fclose($handle);

        //see if the templates extends another and if so, recursivly call the solveInheritance
        if (preg_match('/{%(?:\\s|)extends (?P<baseTemplate>.*)(?:\\s|)%}/', $templateContent, $result))
        {
            $baseTemplate = $result['baseTemplate'];
            $this->solveInheritance(dirname($templateFile)."/".$baseTemplate);

            //get the names of all the blocks in the template.
            preg_match_all('/\\{%(?:\s|)block([\\d]*|) (?P<blocName>[\\w]*)(?:\s|)%\\}/', $templateContent, $blocks);
            foreach($blocks['blocName'] as $item)
            {
                $item = trim($item);
                //get the content of the found block
                //note to self: ATENTIE!!!!! AM PUS AICI UN "?" DUPA ".*" FARA SA VERIFIC DACA MERE
                preg_match('/(?:\\{%(?:\s|)block([\\d]*|) '.$item.'(?:\s|)%\\})(?P<blockContent>.*?)\\{%(?:\s|)end-block\\1(?:\s|)%\\}/s', $templateContent, $blocksContent);
                //replace the block content in the base template with the one from the child template
                $this->template = preg_replace ('/(.*){%(?:\s|)block([\\d]*|) '.$item.'(?:\s|)%}.*?{%(?:\s|)end-block\\2(?:\s|)%}(.*)/s','\\1{%block '.$item.'%}'.$blocksContent['blockContent'].'{%end-block%}\\3',$this->template);
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
        require_once($guard);
        if (!__guard__())
        {
            if (isset($guardFailPage))
                $guarFailPage = new Template($guardFailPage);
            else
                $guarFailPage = new Template("?gurd-failure.tmpl");
            $guarFailPage->write("title","Unauthorized - phpDrone");
            $guarFailPage->render();
            die();
        }
    }

    private function deltaTime()
    {
        return sprintf("%.4f",Utils::microTime()-$this->startTime);
    }

    private function solveVar($input,$php_vars)
    {
        $output = $input;
        preg_match_all('/{%(?P<cont>[^\\}]*)%}/',$output,$vals);
        foreach($vals['cont'] as $f_val)
        {
            $filterPieces = preg_split("/\|/",trim($f_val));
            
            //is a string?
            if (!preg_match('/("|\')(?P<string>[^"\\\\]*(?:\\\\.[^"\\\\]*)*)\\1/', $filterPieces[0],$valCapt))
            {
	            $opParts = preg_split('/(?:\\+)|(?:-)|(?:\\*)|(?:%)|(?:!)|(?:\/)/',trim($filterPieces[0]));
	            if (count($opParts)==1)
	            {

	                $subs = preg_split("/\./",trim($filterPieces[0]));
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
	            }
	            else
	            {
	                $toEval = trim($filterPieces[0]);
	                foreach ($opParts as $part)
	                {
	                    $subs = preg_split("/\./",$part);
	                    if (count($subs)==1)
	                    {
	                        $ev = "\$php_vars['".$subs[0]."']";
	                        eval("\$t_val=$ev;");
	                    }
	                    else
	                    {
	                        $ev = "\$php_vars";
	                        foreach ($subs as $item)
	                            $ev.= "['".$item."']";
	                        eval("\$t_val=$ev;");
	                    }
	                    if ($t_val)
	                        $toEval = preg_replace('/'.addslashes($part).'/',$t_val,$toEval);
	                }
	//                 print "TO EVAL::::::::{$toEval}<br />";
	                eval("\$val=$toEval;");
	            }
            }
            else
                $val = $valCapt['string'];
                
            //apply filters
            if (count($filterPieces)>1)
                for ($f=1;$f<count($filterPieces);$f++)
                {
                    if (preg_match('/(?P<filterName>.*)\\((?P<filterArgs>.+)\\)/',$filterPieces[$f],$capt))
                    {
                        $filterName = $capt['filterName'];
                        $filterArgs = ",".$capt['filterArgs'];
                    }
                    else
                    {
                        $filterName = str_replace(array("(",")"),"",$filterPieces[$f]);
                        $filterArgs = "";
                    }
                    if (function_exists("filter_".$filterName))
                    {
                        eval('$val=filter_'.$filterName.'(\''.addcslashes($val,"'").'\''.$filterArgs.');');
                    }
                    else
                        Utils::throwDroneError(_("Unknown filter").": <b>{$filterName}</b>.");
                }
            $output = preg_replace ('/{%(?:[ ]*|)'.addcslashes(addslashes($f_val),"|+*(.)").'(?:[ ]*|)%}/',$val,$output);
        }
        return $output;
    }

    private function solveIf($input,$php_vars)
    {
        $output = $input;
//                        {%(?:[ ]*|)if([\d]*|) (?P<ifStatement>[^\}]*)%}(?P<ifCont>[^\x00]*?){%(?:[ ]*|)end-if\1(?:[ ]*|)%}
        preg_match_all('/{%(?:[ ]*|)if([\\d]*|) (?P<ifStatement>[^\\}]*)%}/', $output, $ifs);
        
        for($f=0;$f<count($ifs['ifStatement']);$f++)
        {
            $ifStatement = $ifs['ifStatement'][$f];
            $innerIfNumber = $ifs[1][$f];
            
            $toEval = trim($ifStatement);
            $parts = preg_split('/(?:\\+)|(?:-)|(?:\\*)|(?:%)|(?:\/)|(?:\!=)|(?:==)|(?:<=)|(?:>=)|(?:<)|(?:>)|(?:\|\|)|(?:&&)|(?:\()|(?:\))/',$toEval);
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

                    if ($isSet && $val)
                    {
                        if ($type=="string" && $val!="")
                            $val = "'".$val."'";
                        $toEval = preg_replace('/'.addslashes($part).'/',$val,$toEval);
                        if ($toEval=="")
                            $toEval = "False";
                    }
                    else
                        $toEval = preg_replace('/'.addslashes($part).'/',"False",$toEval);
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
                //{%(?:[ ]*|)if([\d]*|) .*%}(?P<ifBlock>[^\x00]*?)(?:(?:{%(?:[ ]*|)else\1(?:[ ]*|)%})(?P<elseBlock>[^\x00]*?))?{%(?:[ ]*|)end-if\1(?:[ ]*|)%}
                preg_match('/{%(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*").'%}(?P<ifBlock>[^\\x00]*?)(?:(?:{%(?:[ ]*|)else'.$innerIfNumber.'(?:[ ]*|)%})(?P<elseBlock>[^\\x00]*?))?{%(?:[ ]*|)end-if'.$innerIfNumber.'(?:[ ]*|)%}/',$output,$capt);
                $output = preg_replace ('/{%(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*").'%}(?:[^\\x00]*?){%(?:[ ]*|)end-if'.$innerIfNumber.'(?:[ ]*|)%}/',rtrim($capt['elseBlock']," "),$output,1);
            }
            else
            {
                preg_match('/{%(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*").'%}(?P<ifCont>[^\\x00]*?){%(?:[ ]*|)end-if'.$innerIfNumber.'(?:[ ]*|)%}/',$output,$capt);
                $ifContent = $capt['ifCont'];
                $ifContent = preg_replace('/(?:(?:{%(?:[ ]*|)else'.$innerIfNumber.'(?:[ ]*|)%})(?P<elseBlock>[^\\x00]*?))[^\\x00].*/','',$ifContent);
                $output = preg_replace ('/{%(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*\\").'%}(?:[^\\x00]*?){%(?:[ ]*|)end-if'.$innerIfNumber.'(?:[ ]*|)%}/',rtrim($ifContent," "),$output,1);
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
                    foreach ($pacient as $key=>$value)
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

                        //process for variables
//                         $builtBlock = preg_replace ('/{%(?:[ ]*|)for-step(?:[ ]*|)%}/',$pas_2,$builtBlock);
                        $this->vars['for_step'] = $pas_2+1;
                        $this->vars['for_index'] = $pas_2;
                        $this->vars['for_key'] = $key;
                        $this->vars['for_total'] = count($pacient);
                        if ($pas_2==0)
                            $this->vars['for_first'] = true;
                        else
                            $this->vars['for_first'] = false;

                        if ($pas_2==$this->vars['for_total']-1)
                            $this->vars['for_last'] = true;
                        else
                            $this->vars['for_last'] = false;


                        
                        $builtBlock = $this->compileTemplate($builtBlock,$this->vars);
                        $newContent .= $builtBlock;
                        $pas_2 ++;
                    }
//                     $blockContent = preg_replace('/([\\\\<{%}>*\/])/','\\\\\1',$blockContent);
                    //$this->template = preg_replace('/{%(?:[ ]*|)for '.$item.' in '.$bunch.'%}'.$blockContent.'{%end-for%}/',$newContent,$this->template);
                    $output = preg_replace('/{%(?:[ ]*|)for([\\d]*|) '.$item.' in '.$bunch.'%}([^\\x00]*?){%end-for\\1%}/',rtrim($newContent," "),$output,1);
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
        global $debugMode;
        global $compressHTML;
        
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
        global $debugMode;
        
        $output = $this->getBuffer();
        if ($debugMode)
        {
            require_once("ver.php");
            $codeSize = sprintf("%.2f", strlen($output)/1024);
            $output .= "<!--This will apear only in debug mode -->\n<div id='droneDebugArea' style='font-size:0.8em;width:100%;border-top:1px solid silver;padding-left:4px;'><b>".$codeSize."</b> kb built in <b>".$this->deltaTime()."</b> seconds.<br />___________<br /><b>phpDrone</b> v{$phpDroneVersion}</div>";
        }
        print $output;

    }

    private function write_p($args)
    {
        if (count($args)>1)
            $this->vars[$args[0]] = $args[1];
        else
            if (count($args)==1)
                $this->vars["body"] .= $args[0];
            else
                Utils::throwDroneError(_("Function takes at least one argument").": <b>Template->write()</b>");
    }

    private function __call($method, $args)
    {
        
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            Utils::throwDroneError(_("Call to undefined method").": <b>Template->".$method."()</b>");
    }
}

?>
