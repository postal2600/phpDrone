<?php

class Meta
{
    //to be implemented
}

class Template
{
    
    private $buffer;
    private $title;
    private $meta;
    private $vars;
    private $guard = "free";
    
    function __construct($template)
    {
        require ("_droneSettings.php");
        if ($debugMode)
            $this->startTime = microtime();
        if (strpos($template,".tmpl"))
            $filename = $template;
        else
            $filename = "templates/{$template}.tmpl";
        
        $this->template = "";
        $this->buildTemplate($filename);
        
        $this->title = "Untitled";
        $this->vars = array();
        $this->vars['meta'] = "";
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

    function setTitle($title)
    {
        $this->title = $title;
    }

    function getTitle()
    {
        return $this->title;
    }

    function setGuard($guard)
    {
        $this->guard = $guard;
    }

    function injectVars($output)
    {
        $result = $output;
        foreach($this->vars as $var => $value)
//             $result = preg_replace ('/{%(?:\\s*|)'.$var.'(?:\\s*|)%}/',$value,$result);
            $result = str_replace("{%{$var}%}",$value,$result);
        return $result;
    }

    function deltaTime()
    {
        $time = microtime();
        return $time-$this->startTime;
    }

    function compileTemplate()
    {
        preg_match_all('/{%(\\s*|)if (?P<ifStatement>.*)%}/', $this->template, $ifs);
        foreach($ifs['ifStatement'] as $ifStatement)
        {
            $statement = trim($ifStatement);
            $v = $this->vars[$statement];
            eval("\$result='".$v."';");
            if (!$result)
                print "###Before".$this->template;
                $this->template = preg_replace ('/(.*){%(?:\s|)if '.$ifStatement.'(?:\s|)%}.*?{%(?:\s|)end-if(?:\s|)%}(.*)/s','',$this->template);
                print "###After".$this->template;
        }
    }

    function getBuffer()
    {
        require ("_droneSettings.php");
//         $this->compileTemplate();
        $output = $this->template;
        //set the page title if one is defined in template
        $output = preg_replace ('/{%(?:\\s*|)title(?:\\s*|)%}/',$this->title,$output);
        $output = $this->injectVars($output);
        //delete the rest of unused vars from template
        $output = preg_replace ('/{%.*%}/',"",$output);
        return $output;
    }

    private function __call($method, $args)
    {
        if ($method=="render")
        {
            require ("_droneSettings.php");
            if ($this->guard!="free")
                require ($this->guard);
            if ($this->guard=="free" || ($this->guard!="free") && __guard__())
            {
                $output = $this->getBuffer();
                if ($debugMode)
                    $output .= "<!--This will apear only in debug mode -->\n<div style='font-size:0.8em;width:100%;border-top:1px solid silver;padding-left:4px;'>Built in <b>".$this->deltaTime()."</b> seconds.<br />___________<br /><b>phpDrone</b> v1.0 BETA</div>";
                print $output;
            }
            else
            {
                if (isset($guardFailPage))
                    $guarFailPage = new Template($guardFailPage);
                else
                    $guarFailPage = new Template("phpDrone/templates/gurd-failure.tmpl");
                $guarFailPage->setTitle("Unauthorized - phpDrone");
                $guarFailPage->render();
            }
        }else
        
        if ($method=="write")
        {
            if (count($args)>1)
                $this->vars[$args[0]] .= $args[1];
            else
                if (count($args)==1)
                    $this->vars["body"] .= $args[0];
                else
                    throw new Exception('Function <b>Template->write()</b> takes at least one argument.');
        }
        else
        {
            //this wil be replaced later with a nicer error
            die("phpDrone error: Call to undefined method Template->".$method."()");
        }

        
    }
}

?>
