<?php

abstract class ViewBase
{
    private $renderMethod;
    private $renderHeadersMethod;

    protected $viewdata;

    private static $viewTypes = array(
        array('Json', FALSE),
        array('Html', TRUE)
    );

    function __construct($viewdata, $requested_type = FALSE) {
        $this->viewdata = $viewdata;

        if ($requested_type === FALSE) {
            $requested_type = $_GET['format'];
        }

        foreach (self::$viewTypes as $type) {
            if ($this->checkViewType($requested_type, $type[0], $type[1])) {
                return;
            }
        }

        die("No usable view type implemented on " . get_class($this));
    }

    private function checkViewType($requested_type, $type, $default = FALSE)
    {
        if (!$default && strcasecmp($requested_type, $type) != 0) {
            return FALSE;
        }

        if (!is_a($this, "I{$type}View")) {
            return FALSE;
        }

        $this->renderMethod = "render{$type}";
        $this->renderHeadersMethod = "render{$type}Headers";

        return TRUE;
    }

    private function renderHeaders()
    {
        $method = $this->renderHeadersMethod;
        $this->$method();
    }

    public function render()
    {
        $this->renderHeaders();

        $method = $this->renderMethod;
        $this->$method();
    }

    # Provide a default implementation for JSON.  A subclass must still
    # implement IJsonView for JSON to be supported.
    public function renderJsonHeaders()
    {
        header('Content-Type: application/json');
    }

    public function renderJson()
    {
        echo json_encode($this->viewdata);
    }
}

interface IHtmlView
{
    public function renderHtmlHeaders();
    public function renderHtml();
}

interface IJsonView
{
    public function renderJsonHeaders();
    public function renderJson();
}

abstract class Controller
{
    public function preExecute()
    {
    }

    public function execute()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';

        $action = "{$action}View";

        $class = new ReflectionClass($this);
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->name;
            if (strcasecmp($action, $name) == 0) {
                return $this->$name();
            }
        }

        return $this->notFoundView();
    }

    public function notFoundView()
    {
        die('View method not found.');
    }
}

class MvcEngine
{
    public static function run($controller)
    {
        $controller->preExecute();

        $view = $controller->execute();
        $view->render();
    }
}

?>
