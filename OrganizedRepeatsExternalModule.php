<?php namespace DE\RUB\OrganizedRepeatsExternalModule;

use Exception;
use ExternalModules\AbstractExternalModule;

require_once "classes/InjectionHelper.php";
require_once "classes/PageInfo.php";
require_once "classes/User.php";

/**
 * Provides enhancements to the External Module Management pages.
 */
class OrganizedRepeatsExternalModule extends AbstractExternalModule {

    #region Constructor & Variables

    /**
     * EM Framework (tooling support)
     * @var \ExternalModules\Framework
     */
    private $fw;

    /**
     * @var InjectionHelper
     */
    public $ih = null;

    private $js_injected = false;

    function __construct() {
        parent::__construct();
        $this->fw = $this->framework;
        $this->ih = InjectionHelper::init($this);
    }

    #endregion

    #region Hooks

    function redcap_data_entry_form ($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
        // Data entry form of an existing record
        if ($record !== null) {
            $Proj = new \Project($project_id);
            $arm_num = $Proj->eventInfo[$event_id]["arm_num"];
            $this->organizeRepeatInstruments($project_id, $record, $arm_num, $instrument, $event_id, $repeat_instance);
        }
    }

    function redcap_every_page_top($project_id) {
        // Record Home Page (of an existing record)
        if (PageInfo::IsExistingRecordHomePage()) {
            $Proj = new \Project($project_id);
            $arm_num = isset($Proj->events[$_GET["arm"]]) ? $_GET["arm"] : "1";
            $record = \Records::recordExists($project_id, $_GET["id"], $arm_num) ? $_GET["id"] : null;
            if ($record !== null) {
                $this->organizeRepeatInstruments($project_id, $record, $arm_num);
            }
        }
        else if (PageInfo::isProjectSetup()) {
            $this->add_setup($project_id);
        }
    }

    function redcap_module_link_check_display($project_id, $link) {
    }

    function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {
        $user = new User($this->fw, $user_id);
        switch($action) {
            case "load-config":
                return $this->load_config($project_id, $user);
            case "save-config":
                return $this->save_config($project_id, $user, $payload);
        }
        return null;
    }

    #endregion

    #region Implementation

    private function organizeRepeatInstruments($project_id, $record, $arm_num, $instrument = null, $event_id = null, $instance = null) {
        $on_rhp = $instrument === null;
        $user = new User($this->fw, defined("USERID") ? USERID : null);
        $config = [
            "mode" => "render",
            "onRHP" => $on_rhp,
        ];

        $this->inject_js();
        $this->initialize_js($config);
    }

    #endregion

    #region Setup

    private function add_setup($project_id) {
        $config = [
            "mode" => "setup",
            "closeBtnText" => js_escape($GLOBALS["lang"]["pub_085"]),
        ];
        $this->inject_js();
        $this->initialize_js($config);
    }

    private function load_config($project_id, $user) {
        $this->require_design_rights($project_id, $user);
        return "TODO";
    }
    
    private function save_config($project_id, $user, $data) {
        $this->require_design_rights($project_id, $user);
        return null;
    }

    private function require_design_rights($project_id, $user) {
        if (!$user->hasDesignRights($project_id)) {
            throw new Exception("Insufficient rights. You must have project design rights to configure Organized Repeats.");
        }
    }

    #endregion

    #region JavaScript initialization

    /**
     * Loads the JS support file and initializes the JSMO (only once)
     */
    private function inject_js() {
        // Only do this once
        if ($this->js_injected) return;
        // Inject JS and CSS
        $this->ih->js("js/organized-repeats.js", PageInfo::IsSurvey());
        $this->ih->css("css/organized-repeats.css", PageInfo::IsSurvey());
        $this->initializeJavascriptModuleObject();
        $this->js_injected = true;
    }

    /**
     * Initializes the JS support file with configuration data
     * @param Array $config 
     * @return void 
     */
    private function initialize_js($config = []) {
        $jsmo_name = $this->getJavascriptModuleObjectName();
        $config["version"] = $this->VERSION;
        $config["debug"] = $this->getProjectSetting("debug-mode") == true;
        // JS
        print "\n<script>$(() => DE_RUB_OrganizedRepeats.init(".json_encode($config).", {$jsmo_name}));</script>\n";
    }

    #endregion

}
