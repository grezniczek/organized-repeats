// Organized Repeats - REDCap External Module
// Dr. Günther Rezniczek, Ruhr-Universität Bochum, Marien Hospital Herne
// @ts-check
;(function() {

//#region Constants & Variables

const moduleNamePrefix = 'DE_RUB_';
const moduleName = 'OrganizedRepeats';

// @ts-ignore
const MODULE = window[moduleNamePrefix + moduleName] ?? {
    init: initialize,
};
// @ts-ignore
window[moduleNamePrefix + moduleName] = MODULE;

let config = {
    debug: false,
    version: '??'
};
let JSMO = {};

let initialized = false;

//#endregion

//#region Initialization

/**
 * Initializes the module
 * @param {Object} config_data 
 * @param {Object} jsmo_obj 
 */
function initialize(config_data, jsmo_obj = null) {
    if (!initialized) {
        if (config_data) {
            config = config_data;
        }
        if (jsmo_obj) {
            JSMO = jsmo_obj;
        }
        initialized = true;
        log('Initialized', config);

        switch(config.mode) {
            case 'setup':
                setupConfig();
                break;
        }
    }
}

//#endregion

//#region Setup

function setupConfig() {
    const orig_initDialog = window['initDialog'];
    const $editDiv = $('<div class="orem-edit-buttons"></div>');
    const $editBtn = $('<button data-orem-action="edit" type="button" disabled class="btn btn-default"><i class="fa-solid fa-wrench text-primary"></i> Organized Repeats</button>');
    const $helpBtn = $('<a href="javascript:;" data-orem-action="help"><i class="fa-solid fa-circle-question fa-lg"></i></a>');
    $editBtn.on('click', editConfig);
    $helpBtn.on('click', editConfigHelp)
    $editDiv.append($editBtn, $helpBtn);
    window['initDialog'] = function(div_id, inner_html) {
        orig_initDialog(div_id, inner_html);
        if (div_id == 'repeatingInstanceEnableDialog') {
            $('#repeatingInstanceEnableDialog').on('dialogopen', function( event, ui ) {
                const $ried = $('#repeatingInstanceEnableDialog').parent();
                log('Repeating Instances Config opened:');
                log($ried);
                if ($ried.find('[data-orem-action]').length == 0) {
                    $ried.find('.ui-dialog-buttonpane').prepend($editDiv);
                    loadConfig($editBtn);
                }
                $ried.find('.ui-dialog-buttonset button.ui-button').each(function() {
                    const $this = $(this);
                    if ($this.text() == config.closeBtnText) {
                        $this.on('click', saveConfig);
                    }
                });
                log('UI installed')
            });
        }
    }
}

function editConfig() {
    log('Editing config');
}

function editConfigHelp() {
    log('Showing config help');
}

function loadConfig($btn) {
    config.data = null;
    JSMO.ajax('load-config').then(function(data) {
        config.data = data
        log('Config loaded:', config.data);
        $btn.prop('disabled', false);
    }).catch(function(err) {
        error('Failed to load config:', err);
    });
}

function saveConfig() {
    if (config.data == null) return; // Nothing to save
    JSMO.ajax('save-config', config.data).then(function(response) {
        log('Config saved');
    }).catch(function(err) {
        error('Failed to save config:', err);
    });
}

//#endregion


//#region Clipboard Helper

/**
 * Copies a string to the clipboard (fallback method for older browsers)
 * @param {string} text
 */
function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
    } catch {
        error('Failed to copy text to clipboard.')
    }
    document.body.removeChild(textArea);
}
/**
 * Copies a string to the clipboard (supported in modern browsers)
 * @param {string} text
 * @returns
 */
function copyTextToClipboard(text) {
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).catch(function() {
        error('Failed to copy text to clipboard.')
    })
}

//#endregion

//#region Debug Logging

function getLineNumber() {
    try {
        const line = ((new Error).stack ?? '').split('\n')[3];
        const parts = line.split(':');
        return parts[parts.length - 2];
    }
    catch(err) {
        return '??';
    }
}
/**
 * Logs a message to the console when in debug mode
 */
function log() {
    if (!config.debug) return;
    log_print(getLineNumber(), 'log', arguments);
}
/**
 * Logs a warning to the console when in debug mode
 */
function warn() {
    if (!config.debug) return;
    log_print(getLineNumber(), 'warn', arguments);
}

/**
 * Logs an error to the console when in debug mode
 */
function error() {
    log_print(getLineNumber(), 'error', arguments);;
}

/**
 * Prints to the console
 * @param {string} ln Line number where log was called from
 * @param {'log'|'warn'|'error'} mode
 * @param {IArguments} args
 */
function log_print(ln, mode, args) {
    const prompt = moduleName + ' ' + config.version + ' [' + ln + ']';
    switch(args.length) {
        case 1:
            console[mode](prompt, args[0]);
            break;
        case 2:
            console[mode](prompt, args[0], args[1]);
            break;
        case 3:
            console[mode](prompt, args[0], args[1], args[2]);
            break;
        case 4:
            console[mode](prompt, args[0], args[1], args[2], args[3]);
            break;
        case 5:
            console[mode](prompt, args[0], args[1], args[2], args[3], args[4]);
            break;
        case 6:
            console[mode](prompt, args[0], args[1], args[2], args[3], args[4], args[5]);
            break;
        default:
            console[mode](prompt, args);
            break;
    }
}

//#endregion

})();