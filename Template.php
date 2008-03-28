<?php
require_once('Filters.php');

if (file_exists('droneEnv/filters.php'))
{
    include('droneEnv/filters.php');
}

class DroneTemplate
{
    
    private $buffer;    
    private $meta;
    public $vars;
    private $guard = "free";
    
    function __construct($template=null,$internal=false)
    {
        
        $debugMode = DroneConfig::get('Main.debugMode');                
        if ($debugMode)
            $this->startTime = microTime(true);
//         $startTime = microTime(true);
        $this->templateFilename = $this->setTemplateFilename($template,$internal);
//         print "construct({$template}): ".sprintf("%.4f",microTime(true)-$startTime);
        $this->templateContent = "";
        $this->vars = array();
    }
    
    private function setTemplateFilename($template,$internal)
    {
        if (isset($template))
        {
            $templateDir = DroneConfig::get('Main.templateDir','templates/');
            if ($internal)
            {
                $droneDir = Utils::getDronePath();
                if (file_exists($templateDir.$template))
                    $templateFilename = $templateDir.$template;
                else
                    $templateFilename = "{$droneDir}/templates/".$template;
            }
            else
                if (file_exists($templateDir.$template))
                    $templateFilename = $templateDir.$template;
                else
                    DroneCore::throwDroneError("Template file was not found: <b>{$template}</b>");
            $this->userTemplateFilename = $template;
            return $templateFilename;
        }
        else
            return null;
    }

    function solveInheritance($templateFile)
    {
        if (!file_exists($templateFile))
             DroneCore::throwDroneError("Template file needed to extend other template was not found: <b>{$templateFile}</b>");
        $handle = fopen($templateFile, "r");
        $templateContent = fread($handle, filesize($templateFile));
        fclose($handle);

        //see if the templates extends another and if so, recursivly call the solveInheritance
        if (preg_match('/<!--(?:\\s|)extends (?P<baseTemplate>.*)(?:\\s|)-->/', $templateContent, $result))
        {
            $baseTemplate = $result['baseTemplate'];
            $this->solveInheritance(dirname($templateFile)."/".$baseTemplate);

            //get the names of all the blocks in the template.
            preg_match_all('/<!--(?:\\s*|)block([\\d]*|) (?P<blocName>[\\w]*)(?:\\s*|)-->/', $templateContent, $blocks);
            foreach($blocks['blocName'] as $item)
            {
                $item = trim($item);
                //get the content of the found block
                //<!--(?:\s|)block([\d]*|) .*?(?:\s|)-->(?P<blockContent>.*?)<!--(?:\s|)/block\1(?:\s|)-->
                preg_match('/<!--(?:\\s|)block([\\d]*|) '.$item.'(?:\\s|)-->(?P<blockContent>.*?)<!--(?:\\s|)\/block\\1(?:\\s|)-->/s', $templateContent, $blocksContent);
                //replace the block content in the base template with the one from the child template
                $this->templateContent = preg_replace ('/(.*)<!--(?:\\s|)block([\\d]*|) '.$item.'(?:\\s|)-->.*?<!--(?:\\s|)\/block\\2(?:\\s|)-->(.*)/s','\\1<!--block '.$item.'-->'.$blocksContent['blockContent'].'<!--/block-->\\3',$this->templateContent);
            }
        }
        else
            $this->templateContent = $templateContent;
    }
    
    function solveInjections($templateFile,$templateContent)
    {
        if (preg_match_all('/<!--(?:\\s|)inject (?P<templatePart>.*)(?:\\s|)-->/', $templateContent, $result))
            foreach($result['templatePart'] as $part)
            {
                $file = dirname($templateFile)."/".$part;
                if (!file_exists($file))
                     DroneCore::throwDroneError("Template file needed to be injected was not found: <b>{$file}</b>");
                $handle = fopen($file, "r");
                $partContent = fread($handle, filesize($file));
                fclose($handle);

                $this->templateContent = preg_replace('/<!--(?:\\s|)inject '.preg_quote($part,'/').'(?:\\s|)-->/',$partContent,$this->templateContent);
            }
    }
    
    private function deltaTime()
    {
        return sprintf("%.4f",microTime(true)-$this->startTime);
    }

    private function parseVars($input,$forId=null)
    {
        $pieces = preg_split('/[\\[\\]]/', $input);
        foreach ($pieces as $piece)
        if ($piece!="")
        {
            $filterPieces = preg_split("/\|/",trim($piece));
            $reservedVars = array('drone_for_step'=>"\$drone_for_index_{$forId}+1",
                                  'drone_for_index'=>"\$drone_for_index_{$forId}",
                                  'drone_for_total'=>"\$drone_for_total_{$forId}",
                                  'drone_for_first'=>"\$drone_for_index_{$forId}==0",
                                  'drone_for_last'=>"\$drone_for_index_{$forId}==\$drone_for_total_{$forId}-1",
                                  'null'=>'null'
                                 );

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
                                $ev = array_key_exists($subs[0],$reservedVars)?$reservedVars[$subs[0]]:"\${$subs[0]}";
                            else
                                $ev = $subs[0];
                      else
                      {

                        $skipOne = true;
                        $ev = "\${$subs[0]}";

                        foreach ($subs as $item)
                            if ($skipOne)
                                $skipOne = false;
                            else
                                if (is_numeric($item))
                                    $ev .= "[".$item."]";
                                else
                                    $ev .= "['".$item."']";
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
                                $ev = array_key_exists($subs[0],$reservedVars)?$reservedVars[$subs[0]]:"\${$subs[0]}";
                            else
                                $ev = $subs[0];
                        else
                        {

                            $skipOne = true;
                            $ev = "\${$subs[0]}";

                            foreach ($subs as $item)
                                if ($skipOne)
                                    $skipOne = false;
                                else
                                    if (is_numeric($item))
                                        $ev .= "[".$item."]";
                                    else
                                        $ev .= "['".$item."']";
                        }
                        $toEval = preg_replace('/'.addslashes($part).'/',$ev,$toEval);
                    }
                      $ev = $toEval;
                }
            }
            else
            {
                $string = addcslashes($valCapt['string'],"'");
                $ev = "'{$string}'";
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
                        DroneCore::throwDroneError("Unknown filter: <b>{$filterName}</b>.");
                }
            $input = preg_replace('/'.preg_quote($piece,'/|').'/',$ev,$input);
        }
        return $input;
    }

    private function solveVar($input,$forId)
    {
        $output = $input;
        preg_match_all('/<!--(?!(?:\\s*)REM|cycle)(?P<cont>.*?)-->/s',$output,$vals);
        foreach($vals['cont'] as $f_val)
        {
            $ev = $this->parseVars($f_val,$forId);
            //TODO: tailing \n-s are not preserved.
            $output = preg_replace ('/<!--(?:[ ]*|)'.preg_quote($f_val,'/').'(?:[ ]*|)-->/',"<?php echo {$ev}; ?>",$output);
        }
        return $output;
    }

    private function solveIf($input,$forId)
    {
        $output = $input;
        //<!--(?:[ ]*|)if([\d]*|) (?P<ifStatement>.*?)-->(?P<ifCont>.*?)<!--(?:[ ]*|)/if\1(?:[ ]*|)-->
        preg_match_all('/<!--(?:[ ]*|)if([\\d]*|) (?P<ifStatement>.*?)-->/s', $output, $ifs);
        
        for($f=0;$f<count($ifs['ifStatement']);$f++)
        {
            $ifStatement = $ifs['ifStatement'][$f];
            $innerIfNumber = $ifs[1][$f];
            
            $toEval = trim($ifStatement);
            $parts = preg_split('/(?:\!=)|(?:\!)|(?:\+)|(?:-)|(?:\*)|(?:%)|(?:\/)|(?:==)|(?:<=)|(?:>=)|(?:<)|(?:>)|(?:\|\|)|(?:&&)|(?:\()|(?:\))/',$toEval);
            if (count($parts)!=1)
            {
                foreach ($parts as $part)
                {
                    $ev = $this->parseVars($part,$forId);
                    $toEval = preg_replace('/'.preg_quote($part,'|/').'/',$ev,$toEval);
                }
            }
            else
                $toEval = $this->parseVars(trim($toEval),$forId);
            
            //<!--(?:[ ]*|)if([\d]*|) .*-->(?P<ifBlock>.*?)(?:(?:<!--(?:[ ]*|)else\1(?:[ ]*|)-->)(?P<elseBlock>.*?))?<!--(?:[ ]*|)/if\1(?:[ ]*|)-->
            preg_match('/<!--(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*[]|").'-->(?P<ifBlock>.*?)(?:(?:(?:[ ]*|)<!--(?:[ ]*|)else'.$innerIfNumber.'(?:[ ]*|)-->)(?P<elseBlock>.*?))?(?:[ ]*|)<!--(?:[ ]*|)\/if'.$innerIfNumber.'(?:[ ]*|)-->/s',$output,$capt);
            if (rtrim($capt['elseBlock'])!="")
                $cElseBlock = "<?php }else{ ?>".rtrim($capt['elseBlock']," ");
            else
                $cElseBlock = "";
            
            $output = preg_replace ('/(?:[ ]{2,}|)<!--(?:[ ]*|)if'.$innerIfNumber.' '.addcslashes($ifStatement,"+*[]|").'-->(?:.*?)<!--(?:[ ]*|)\/if'.$innerIfNumber.'(?:[ ]*|)-->(?:[\\n]|)/s',"<?php if ({$toEval}){?>".$capt['ifBlock'].$cElseBlock."<?php } ?>",$output,1);
        }
        return $output;
    }

    private function solveFor($input,$forId)
    {
        $output = $input;
        preg_match_all('/<!--(?:\\s*)for([\\d]*|) (?P<item>.*) in (?P<bunch>.*?)-->/', $output, $fors);
        $pas = 0;
        foreach($fors['bunch'] as $bunch)
        {
            $forId = md5(microtime());
            $item = $fors['item'][$pas];
            //<!--(?:[ ]|)for([\d]*|) (?:.*?) in (?:.*?)-->(?:[\\n]|)(?P<forblock>.*?)(?:[ ]*|)<!--/for\1-->
            // get the if block content
            preg_match('/<!--(?:\\s*)for([\\d]*|) '.$item.' in '.$bunch.'-->(?:[\\n]|)(?P<forblock>.*?)(?:\\s*)<!--(?:\\s*)\/for\\1(?:\\s*)-->/s', $output, $forBlocksContent);
            $blockContent = $forBlocksContent['forblock'];


            $builtBlock = $blockContent;
            $f_vars = preg_match_all('/<!--(?:[ ]*|)(?P<cont>'.$item.'.*?)-->/s',$builtBlock,$varCapt);
            
            $builtBlock = $this->compileTemplate($builtBlock,$forId);
            $droneBunch = $this->parseVars($bunch,$forId);

            $droneItem = implode('=>$',preg_split('/,/',$item));

            if ( preg_match('/\\$drone_for_step|\\$drone_for_index|\\$drone_for_total|\\$drone_for_first|\\$drone_for_last/',$builtBlock) )
            {
                $forSet = "\$drone_for_total_{$forId}=count(\$for_{$forId}_value);".
                          "\$drone_for_index_{$forId}=0;";

                $forUnSet = "unset(\$drone_for_total_{$forId});".
                            "unset(\$drone_for_index_{$forId});";

                $forIncrement = "\$drone_for_index_{$forId}++;";
            }

            
            if (preg_match_all('/<!--(?:\\s*)cycle (?P<elems>.*?)-->/s', $builtBlock, $capt))
            {
                foreach ($capt['elems'] as $cyc_item)
                {
                    $partsArray = 'array("'.implode('","',preg_split('/\,/',addcslashes($cyc_item,'"'))).'")';
                    $builtBlock = preg_replace('/<!--(?:\\s*)cycle '.preg_quote($cyc_item,'/"').'-->/s','<?php echo Utils::array_get($drone_for_index_'.$forId.'%count('.$partsArray.'),'.$partsArray.');?>',$builtBlock);
                }
                $forSet = "\$drone_for_total_{$forId}=count(\$for_{$forId}_value);".
                          "\$drone_for_index_{$forId}=0;";

                $forUnSet = "unset(\$drone_for_total_{$forId});".
                            "unset(\$drone_for_index_{$forId});";

                $forIncrement = "\$drone_for_index_{$forId}++;";
            }
            
            $output = preg_replace('/(?:[ ]{2,}|)<!--(?:\\s*)for([\\d]*|) '.$item.' in '.$bunch.'-->(?:[\\n]|)'.preg_quote($blockContent,'/').'(?:\\s*)<!--(?:\\s*)\/for\\1(?:\\s*)-->/',"<?php \$for_{$forId}_value=is_array({$droneBunch})||is_object({$droneBunch})||is_numeric({$droneBunch})?DroneTemplate::int2array({$droneBunch}):array(); {$forSet} foreach(\$for_{$forId}_value as \${$droneItem}) {?>{$builtBlock}<?php {$forIncrement}} {$forUnSet} ?>",$output);
            $pas++;
        }
        return $output;
    }
    
    function compileTemplate($input,$forId=null)
    {
        $output = $input;
        //process reminders
        $output = preg_replace('/(<\\?.*?\\?>)/s',"<?php echo addcslashes('$1',\"'\"); ?>\n",$output);
        $output = $this->solveFor($output,$forId);
        $output = $this->solveIf($output,$forId);
        $output = $this->solveVar($output,$forId);
        $output = preg_replace('/<!--(?:\\s*)REM(.*?)-->/s', "<!--$1 -->", $output);
        //delete the rest of unused vars from template
//         $output = preg_replace ('/<!--[^\\}]*-->/',"",$output);
        return $output;
    }

    static function int2array($int)
    {
        if (is_numeric($int))
            return range(0,$int);
        else
            return $int;
    }

    function prepareTemplate($templateName,$internal)
    {
        if (!isset($this->templateFilename) && !$this->templateFilename = $this->setTemplateFilename($templateName,$internal))
            DroneCore::throwDroneError("You must set the template you want to render.");
            
        $fileInfo = pathinfo($this->templateFilename);
        if (strtolower($fileInfo['extension'])!='php')
        {

            $cacheDir = DroneConfig::get('Main.cacheDir');
            $appMode = DroneConfig::get('Main.appMode');
            $debugMode = DroneConfig::get('Main.debugMode');
            
            if ($appMode=='development')
            {
                $this->solveInheritance($this->templateFilename);
                $this->solveInjections($this->templateFilename,$this->templateContent);
                
                $devAdd = md5($this->templateContent);
            }
            else
                $devAdd = "";
            
            if (isset($cacheDir) && is_dir($cacheDir))
                $cDir = realpath($cacheDir);
            elseif (is_dir(Utils::getTempDir()."/phpDroneCache") || mkdir(Utils::getTempDir()."/phpDroneCache"))
                $cDir = Utils::getTempDir()."/phpDroneCache";
            else
                DroneCore::throwDroneError("Could not create the cache directory.");

            
            $outFilename = "{$cDir}/".$this->userTemplateFilename.$devAdd.".php";

            // if file is cached and, no need to rebuild
            if (file_exists($outFilename))
                return $outFilename;

            $dirStructure = preg_split('/\/|\\\\/',$this->userTemplateFilename.$devAdd.".php");
            array_pop($dirStructure);
            $pathSoFar = "{$cDir}/";
            foreach($dirStructure as $dir)
            {
                $pathSoFar .= "$dir/";
                if (!@is_dir($pathSoFar) && !@mkdir($pathSoFar))
                    DroneCore::throwDroneError("Could not create directory in cache directory:{$cDir}");
            }
            $handler = fopen($outFilename,'w');
            if (!$handler)
                DroneCore::throwDroneError("Could not write file in the cache directory. Please change the write persission to <b>{$cDir}</b>");

            if ($appMode!='development')
            {
                $this->solveInheritance($this->templateFilename);
                $this->solveInjections($this->templateFilename,$this->templateContent);
            }

            //clear the block-related tags
            $this->templateContent = preg_replace('/<!--block([\\d]*|) .*?-->|<!--\/block([\\d]*|)-->/',"",$this->templateContent);
            $output = $this->compileTemplate($this->templateContent);
            require("ver.php");

            $output = "<?php \n\n /* Compiled with phpDrone v{$phpDroneVersion} from {$this->userTemplateFilename} */ \n\n ?>\n{$output}";
            fwrite($handler,$output);
            fclose($handler);
        }
        else
            $outFilename = $this->templateFilename;
        
        return $outFilename;
    }

    function getBuffer($templateName=null,$internal=false)
    {
        $templateFile = $this->prepareTemplate($templateName,$internal);
        ob_start();
        extract($this->vars);
        include $templateFile;
        $content = ob_get_contents();
        ob_end_clean();

        $compressHTML = DroneConfig::get('Main.compressHTML');
        if ($compressHTML)
            $content = preg_replace('/[\s]{2,}/', ' ',preg_replace('/\n|\r\n|\t/', '', $content));

        return $content;
    }

    private function render_p($args)
    {
        $output = $this->getBuffer($args[0],$args[1]);
        $endTime = $this->deltaTime();
        $debugMode = DroneConfig::get('Main.debugMode');
        if ($debugMode)
        {
            DroneProfiler::buildResults();
            require("ver.php");

            $tmpl = new DroneTemplate("core/debug.tmpl",true);
            $tmpl->set('droneVersion',$phpDroneVersion);
            $tmpl->set('codeSize',sprintf("%.2f", strlen($output)/1024));
            $tmpl->set('time',$endTime);
            $profilerTimes = array();
            if (isset($_SESSION['droneProfilerTimes']))
            {
                foreach ($_SESSION['droneProfilerTimes'] as $key=>$times)
                {
                    $tmpArr = $times;
                    sort($tmpArr);
                    $profilerTimes[$key] = array();
                    $profilerTimes[$key]['now'] = sprintf("%.4f",$times[count($times)-1]);
                    $profilerTimes[$key]['prev'] = sprintf("%.4f",$times[count($times)-2]);
                    $profilerTimes[$key]['best'] = sprintf("%.4f",$tmpArr[0]);
                    $profilerTimes[$key]['worst'] = sprintf("%.4f",$tmpArr[count($tmpArr)-1]);
                    $profilerTimes[$key]['avg'] = sprintf("%.4f",array_sum($times)/count($times));
                }
                $tmpl->set('profilerTimes',$profilerTimes);
                $tmpl->set('consoleText',' ');
            }
//             $tmpl->set('consoleText',"Test console<br /><br /><br /><br /><br /><br /><br />End test");
            $output .= $tmpl->getBuffer();
        }
        print $output;
    }

    private function set_p($args)
    {
        $this->vars[$args[0]] = $args[1];
    }

    private function __call($method, $args)
    {
        
        if (method_exists($this,$method."_p"))
            eval("\$this->".$method."_p(\$args);");
        else
            DroneCore::throwDroneError("Call to undefined method: <b>Template->".$method."()</b>");
    }
}

?>
