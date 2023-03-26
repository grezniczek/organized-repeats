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

    function redcap_data_entry_form ($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1) {

        $this->organizeRepeatInstruments();
    }

    function redcap_every_page_top($project_id = null) {

        // Record Home Page (of an existing record)
        if (PageInfo::IsExistingRecordHomePage()) {
            $this->organizeRepeatInstruments();
        }
    }

    function redcap_module_link_check_display($project_id, $link) {
    }

    function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {
        $user = new User($this->fw, $user_id);
        switch($action) {
        }
        return null;
    }

    #endregion

    #region Implementation

    private function organizeRepeatInstruments() {
        $user = new User($this->fw, defined("USERID") ? USERID : null);
        $config = [];

        $this->inject_js();
        $this->initialize_js($config);
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
