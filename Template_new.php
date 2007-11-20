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
                $templateFilename = substr($template,1);
            else
            if (isset($templateDir) && file_exists($templateDir.substr($template,1)))
                $templateFilename = $templateDir.substr($template,1);
            else
                if (file_exists("templates/".substr($template,1)))
                    $templateFilename = "templates/".substr($template,1);
                else
                    $templateFilename = "{$droneDir}/templates/".substr($template,1);
        }
        else
            if (file_exists($template))
                $templateFilename = $template;
            else
            if (isset($templateDir))
                $templateFilename = $templateDir.$template;
            else
                if (file_exists("templates/".$template))
                    $templateFilename = "templates/".$template;
                else
                    Utils::throwDroneError("Template file was not be found: <b>templates/{$template}</b>");
                
        $this->template = "";
        $this->buildTemplate($templateFilename);
        $this->templateFilename = $template;
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
            Utils::throwDroneError("Template file needed to extend other template was not found: <b>{$templateFile}</b>");
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

    private function parseVars($input,$forVar=null)
    {
        $filterPieces = preg_split("/\|/",trim($input));

        if ($filterPieces[0]!=$forVar)
        {
            //is a string?
            if (!preg_match('/("|\')(?P<string>[^"\\\\]*(?:\\\\.[^"\\\\]*)*)\\1/', $filterPieces[0],$valCapt))
            {
              $opParts = preg_split('/(?:\\+)|(?:-)|(?:\\*)|(?:%)|(?:!)|(?:\/)/',trim($filterPieces[0]));
              if (count($opParts)==1)
              {

                  $subs = preg_split("/\./",trim($filterPieces[0]));
                  if (count($subs)==1)
                        //detect if it's a number or a var name (TODO: I should check if string)
                        if (!intval($subs[0]))
                         $ev = "\$phpDrone_vars['".$subs[0]."']";
                        else
                            $ev = $subs[0];
                  else
                  {
                      $ev = "\$phpDrone_vars";
                      foreach ($subs as $item)
                          $ev.= "['".$item."']";
                  }
              }
              else
              {
                  $toEval = trim($filterPieces[0]);
                  foreach ($opParts as $part)
                  {
                      $subs = preg_split("/\./",$part);
                      if (count($subs)==1)
                            //detect if it's a number or a var name (TODO: I should check if string)
                            if (!intval($subs[0]))
                             $ev = "\$phpDrone_vars['".$subs[0]."']";
                            else
                                $ev = $subs[0];
                      else
                      {
                          $ev = "\$phpDrone_vars";
                          foreach ($subs as $item)
                              $ev.= "['".$item."']";
                      }
                      $toEval = preg_replace('/'.addslashes($part).'/',$ev,$toEval);
                  }
                    $ev = $toEval;
              }
            }
            else
                $ev = "'{$valCapt['string']}'";
        }
        else
        {
            $ev = "\${$filterPieces[0]}";
        }

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
                    $ev = "filter_{$filterName}({$ev}{$filterArgs})";
                }
                else
                    Utils::throwDroneError("Unknown filter: <b>{$filterName}</b>.");
            }

        return $ev;
    }

    private function solveVar($input,$forVar)
    {
        $output = $input;
        preg_match_all('/{%(?P<cont>[^\\}]*)%}/',$output,$vals);
        foreach($vals['cont'] as $f_val)
        {
            $ev = $this->parseVars($f_val,$forVar);
            $output = preg_replace ('/{%(?:[ ]*|)'.addcslashes(addslashes($f_val),"|+*(.)").'(?:[ ]*|)%}/',"<?={$ev}; ?>",$output);
        }
        return $output;
    }

    private function solveIf($input,$forVar)
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
                    $ev = $this->parseVars($part,$forVar);
                    $toEval = preg_replace('/'.addslashes($part).'/',$ev,$toEval);
                }
            }
            else
                $toEval = $this->parseVars(trim($toEval));

            //{%(?:[ ]*|)if([\d]*|) .*%}(?P<ifBlock>[^\x00]*?)(?:(?:{%(?:[ ]*|)else\1(?:[ ]*|)%})(?P<elseBlock>[^\x00]*?))?{%(?:[ ]*|)end-if\1(?:[ ]*|)%}
            preg_match('/{%(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*").'%}(?P<ifBlock>[^\\x00]*?)(?:(?:{%(?:[ ]*|)else'.$innerIfNumber.'(?:[ ]*|)%})(?:[\\n]|)(?P<elseBlock>[^\\x00]*?))?{%(?:[ ]*|)end-if'.$innerIfNumber.'(?:[ ]*|)%}/',$output,$capt);
            $output = preg_replace ('/(?:[ ]{2,}|){%(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*").'%}(?:[^\\x00]*?){%(?:[ ]*|)end-if'.$innerIfNumber.'(?:[ ]*|)%}(?:[\\n]|)/',"<?php if ({$toEval}) {?>".rtrim($capt['ifBlock'])."<?php }else{ ?>".rtrim($capt['elseBlock']," ")."<?php } ?>",$output,1);

        }
        return $output;
    }

    private function solveFor($input,$forVar)
    {
        $output = $input;
        preg_match_all('/{%(?:[ ]|)for([\\d]*|) (?P<item>.*) in (?P<bunch>.*?)%}/', $output, $fors);
        $pas = 0;
        foreach($fors['bunch'] as $bunch)
        {
            $item = $fors['item'][$pas];
            //{%(?:[ ]|)for([\d]*|) (?:.*?) in (?:.*?)%}(?:[\\n]|)(?P<forblock>[^\x00]*?)(?:[ ]*|){%end-for\1%} - get the block
            // get the if block content
            preg_match('/{%(?:[ ]|)for([\\d]*|) '.$item.' in '.$bunch.'%}(?P<forblock>[^\\x00]*?){%end-for\\1%}/', $output, $forBlocksContent);
            $blockContent = $forBlocksContent['forblock'];
            
            $builtBlock = $blockContent;
            $f_vars = preg_match_all('/{%(?:[ ]*|)(?P<cont>'.$item.'[^\\x00]*?)%}/',$builtBlock,$varCapt);
            
            $builtBlock = $this->compileTemplate($builtBlock,$item);
            $droneBunch = $this->parseVars($bunch);

            $output = preg_replace('/(?:[ ]{2,}|){%(?:[ ]|)for([\\d]*|) '.$item.' in '.$bunch.'%}'.preg_quote($blockContent,'/').'{%end-for\\1%}/',"<?php foreach(is_array({$droneBunch})||is_object({$droneBunch})?{$droneBunch}:is_numeric($droneBunch)?range(0,$droneBunch):array() as \${$item}) {?> {$builtBlock} <?php } ?>",$output);
            $pas++;
        }
        return $output;
    }
    
    function compileTemplate($input,$forVar=null)
    {
        $output = $input;
        $output = $this->solveFor($output,$forVar);
        $output = $this->solveIf($output,$forVar);
        $output = $this->solveVar($output,$forVar);
        //delete the rest of unused vars from template
        $output = preg_replace ('/{%[^\\}]*%}/',"",$output);
        return $output;
    }

    function prepareTemplate()
    {
        global $debugMode;
        global $compressHTML;
        global $cacheDir;

        $output = $this->compileTemplate($this->template,$this->vars);
        //take out reminders
        $output = preg_replace('/{%(?:[ ]*|)rem(?:[ ]*|)%}(?:[^\\x00]*){%(?:[ ]*|)end-rem(?:[ ]*|)%}/', '', $output);
        if (isset($compressHTML) && $compressHTML)
        {
            $output = preg_replace('/\n|\r\n|\t/', '', $output);
            $output = preg_replace('/[\s]{2,}/', ' ', $output);
        }

        if (isset($cacheDir) && is_dir($cacheDir))
            $cDir = realpath($cacheDir);
        elseif (is_dir(Utils::getTempDir()."/phpDroneCache") || mkdir(Utils::getTempDir()."/phpDroneCache"))
            $cDir = Utils::getTempDir()."/phpDroneCache";
        else
            Utils::throwDroneError("Could not create the cache directory.");

        $outFilename = "{$cDir}/".md5($output).".php";
        
        if (file_exists($outFilename))
            return $outFilename;

        $handler = fopen($outFilename,'w');
        if (!$handler)
            Utils::throwDroneError("Could not write file in the cache directory. Please change the write persission to <b>{$cDir}</b>");

        require("ver.php");
        $output = "<?php \n\n /* Compiled with phpDrone v{$phpDroneVersion} from {$this->templateFilename} */ \n\n ?>{$output}";
        fwrite($handler,$output);
        fclose($handler);
        
        return $outFilename;
    }

    function getBuffer()
    {
        $templateFile = $this->prepareTemplate();
        if (is_file($templateFile))
        {
            ob_start();
            $phpDrone_vars = $this->vars;
            include $templateFile;
            $content = ob_get_contents();
            ob_end_clean();
        }
        else
            Utils::throwDroneError("Error reading cache!");
        
        return $content;
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
                Utils::throwDroneError("Function takes at least one argument: <b>Template->write()</b>");
    }

    private function __call($method, $args)
    {
        
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            Utils::throwDroneError("Call to undefined method: <b>Template->".$method."()</b>");
    }
}

?>
