<?
  header("Content-type: text/javascript");
?>
/**
/****************************************************************
* findPos script from http://www.quirksmode.org/js/findpos.html *
****************************************************************/
function findPos(obj) {
  var curleft = curtop = 0;
  if (obj.offsetParent) {
    curleft = obj.offsetLeft
    curtop = obj.offsetTop
    while (obj = obj.offsetParent) {
      curleft += obj.offsetLeft
      curtop += obj.offsetTop
    }
  }
  return [curleft,curtop];
}


/************************************************************
* Extensions to the YUI AutoComplete widget                 *
************************************************************/
/**
 * Handles textbox keydown events of functional keys, mainly for UI behavior.
 * This is a patched version for the bug being tracked at
 * http://sourceforge.net/tracker/index.php?func=detail&aid=1779618&group_id=165715&atid=836476
 *
 * @method _onTextboxKeyDown
 * @param v {HTMLEvent} The keydown event.
 * @param oSelf {YAHOO.widget.AutoComplete} The AutoComplete instance.
 * @private
 */
YAHOO.widget.AutoComplete.prototype._onTextboxKeyDown = function(v,oSelf) {
    var nKeyCode = v.keyCode;
    switch (nKeyCode) {
        case 9: 
            if(oSelf._oCurItem) {
                if(oSelf.delimChar && (oSelf._nKeyCode != nKeyCode)) {
                    if(oSelf._bContainerOpen) {YAHOO.util.Event.stopEvent(v);}
                }
                oSelf._selectItem(oSelf._oCurItem);
            }
            else {oSelf._toggleContainer(false);}
            break;
        case 13:
            var isMac = (navigator.userAgent.toLowerCase().indexOf("mac") != -1);
            if(!isMac) {
                if(oSelf._oCurItem) {
                    if(oSelf._nKeyCode != nKeyCode) {
                        if(oSelf._bContainerOpen) {YAHOO.util.Event.stopEvent(v);}
                    }
                    oSelf._selectItem(oSelf._oCurItem);
                }
                else {oSelf._toggleContainer(false);}
            }
            break;
        case 27:
            oSelf._toggleContainer(false);
            return;
        case 39:
            oSelf._jumpSelection();
            break;
        case 38:
            YAHOO.util.Event.stopEvent(v);
            oSelf._moveSelection(nKeyCode);
            break;
        case 40:
            YAHOO.util.Event.stopEvent(v);
            oSelf._moveSelection(nKeyCode);
            break;
        default:
            break;
    }
};

/**
 * Retrieves validated terms given a term or delimited set of terms. Currently only 
 * implemented for XHR data sources, although there is some rudimentary javascript 
 * checking of returned terms to make sure they match, so it should be easy to
 * extend for other data sources.
 *
 * @method getValidResults
 * @param oCallbackFn {HTMLFunction} Callback function defined by oParent object to which to return results.
 * @param sQuery {String} The term or delimited set of terms to validate.
 * @param oParent {Object} The object instance that has requested data.
 * @param sDelimiter {Object} The object instance that has requested data.
 */
YAHOO.widget.DataSource.prototype.getValidResults = function(oCallbackFn, sQuery, oParent, sDelimiter) {
    this.getResults(oCallbackFn, sQuery, oParent);
};

/**
 * XHR implementation of getValidResults
 *
 * @method getValidResults
 * @param oCallbackFn {HTMLFunction} Callback function defined by oParent object to which to return results.
 * @param sQuery {String} The term or delimited set of terms to validate.
 * @param oParent {Object} The object instance that has requested data.
 * @param sDelimiter {Object} The object instance that has requested data.
 */
YAHOO.widget.DS_XHR.prototype.getValidResults = function(oCallbackFn, sQuery, oParent, sDelimiter) {
    var oldAppend = this.scriptQueryAppend;
    this.scriptQueryAppend = 
      "validating=validating&delimiter="  + encodeURIComponent(sDelimiter) + "&" +
      this.scriptQueryAppend;

    this.getResults(oCallbackFn, sQuery, oParent);

    this.scriptQueryAppend = oldAppend;
};

/**
 * Whether or not to force the user's selection to match one of the query
 * results. This version runs the current value of the text box through a 
 * backend DataSource which will hopefully return only valid results.
 * Results are also run through a formatter before being returned to the 
 * text box. Unlike the standard forceSelection, forceSelectionDelayed will 
 * properly handle delimited items by filtering out bad ones.
 *
 * @property forceSelectionDelayed
 * @type Boolean
 * @default false
 */
YAHOO.widget.AutoComplete.prototype.forceSelectionDelayed = false;

/**
 * Given the current value of the input box, send it off to the backend script
 * for validation. The backend script's query string is appended with two variables 
 * to indicate that it should be validating: &validating=validating&delimiter=<delimiter>.
 *
 * @method validateValues
 * @param sQuery {String} The current value of the autocomplete's input box.
 */
YAHOO.widget.AutoComplete.prototype.validateValues = function(sQuery) {
    if(this.minQueryLength == -1) {
        this._toggleContainer(false);
        return false;
    }
    if((sQuery && (sQuery.length < this.minQueryLength)) || (!sQuery && this.minQueryLength > 0)) {
        if(this._nDelayID != -1) {
            clearTimeout(this._nDelayID);
        }
        this._toggleContainer(false);
        return false;
    }

    sQuery = encodeURIComponent(sQuery);
    this.dataRequestEvent.fire(this, sQuery);
    this.dataSource.getValidResults(this._finishValidating, sQuery, this, this.delimChar);
    this._nDelayID = -1;    // Reset timeout ID because request has been made
    sQuery = this.doBeforeSendQuery(sQuery);

};

/**
 * When the request started by validateValues returns, the results need to
 * be formatted and put into the associated input box.
 *
 * @method _finishValidating
 * @param sQuery {String} The string that is being validated.
 * @param aResults {Object[]} An array of validated result objects from the DataSource.
 * @param oSelf {YAHOO.widget.AutoComplete} The AutoComplete instance.
 */
YAHOO.widget.AutoComplete.prototype._finishValidating = function(sQuery, aResults, oSelf) {
    var textBox = oSelf._oTextbox;
    filteredValue = "";
    sQuery = decodeURIComponent(sQuery);
    var filteredResults = new Array();
    for (var i = 0; i < aResults.length; i++) {
	var aSentElements = (oSelf.delimChar && oSelf.delimChar != null && oSelf.delimChar.length > 0) ? sQuery.split(oSelf.delimChar) : new Array(sQuery);
	var exactMatch = false;
	for (var j = 0; j < aSentElements.length; j++) {
	    if (aResults[i][0] == aSentElements[j].replace(/^\s+|\s+$/, '')) {
		filteredResults[filteredResults.length] = aResults[i];
		break;
	    }
	}
    }
    for (var i = 0; i < filteredResults.length; i++) {
        filteredValue += filteredResults[i][0];
        if (i < filteredResults.length - 1) {
            filteredValue += oSelf.delimChar + " ";
        }
    }
    textBox.value = filteredValue;
    oSelf.finishedValidatingEvent.fire(oSelf, filteredResults);
};

/**
 * Extends the default _onTextBoxBlur handler to check and see if we should do 
 * our custom validation.
 *
 * @override
 * @method _onTextboxBlur
 * @param v {HTMLEvent} The focus event.
 * @param oSelf {YAHOO.widget.AutoComplete} The AutoComplete instance
 * @private
 */
YAHOO.widget.AutoComplete.prototype._oldOnTextboxBlur = YAHOO.widget.AutoComplete.prototype._onTextboxBlur;
YAHOO.widget.AutoComplete.prototype._onTextboxBlur = function (v,oSelf) {
    // Don't treat as a blur if it was a selection via mouse click
    if(!oSelf._bOverContainer || (oSelf._nKeyCode == 9)) {
        if (oSelf.forceSelectionDelayed) {
            var sText = this.value; //string in textbox
            // Query the backend
            oSelf.validateValues(sText);
        }
    }
    // Call extended function:
    oSelf._oldOnTextboxBlur(v, oSelf);
};

/**
 * Extra keyCodes to be used as a selection key (for example, the delimiter).
 *
 * @property extraSelectionKeycodes
 * @type int | int[]
 */
YAHOO.widget.AutoComplete.prototype.extraSelectionKeycodes = null;

/**
 * Extends _onTextboxKeyDown to allow use of other keycodes (such as the delimiter) for
 * selection.
 *
 * @override
 * @method _onTextboxKeyDown
 * @param v {HTMLEvent} The keydown event.
 * @param oSelf {YAHOO.widget.AutoComplete} The AutoComplete instance.
 * @private
 */
YAHOO.widget.AutoComplete.prototype._oldOnTextboxKeyDown = YAHOO.widget.AutoComplete.prototype._onTextboxKeyDown;
YAHOO.widget.AutoComplete.prototype._onTextboxKeyDown = function(v,oSelf) {
    var nKeyCode = v.keyCode;
    var extraKeys = oSelf.extraSelectionKeycodes;
    if (extraKeys != null) {
        if (!YAHOO.lang.isArray(extraKeys)) {
            oSelf.extraSelectionKeycodes = [extraKeys];
            extraKeys = oSelf.extraSelectionKeycodes;
        }
        for (var i = 0; i < extraKeys.length; i++) {
            if (nKeyCode == extraKeys[i]) {
                if(oSelf._oCurItem) {
                    if(oSelf._nKeyCode != nKeyCode) {
                        if(oSelf._bContainerOpen) {
                            YAHOO.util.Event.stopEvent(v);
                        }
                    }
                    oSelf._selectItem(oSelf._oCurItem);
                } else {
                    oSelf._toggleContainer(false);
                }
                break;
            }
        }
    }
    oSelf._oldOnTextboxKeyDown(v, oSelf);
}

YAHOO.widget.AutoComplete.prototype.finishedValidatingEvent = null;

YAHOO.widget.AutoComplete.prototype._oldInitContainer = YAHOO.widget.AutoComplete.prototype._initContainer;
YAHOO.widget.AutoComplete.prototype._initContainer = function() {
  this.finishedValidatingEvent = new YAHOO.util.CustomEvent("finishedValidating", this);
  this._oldInitContainer();
}; 

YAHOO.widget.AutoComplete.prototype.oldDestroy = YAHOO.widget.AutoComplete.prototype.destroy;
YAHOO.widget.AutoComplete.prototype.destroy = function() {
  this.finishedValidatingEvent.unsubscribe();
  this.oldDestroy();
}


/******************************************************
* DBField specific code here			      *
*******************************************************/

var DBFields_showURL = function(sType, aArgs) {
  var oCompleter = aArgs[0];
  var aResults = aArgs[1];
  if (oCompleter.urlField) {
    var urlField = document.getElementById(oCompleter.urlField);
    if (urlField) {
      urlField.innerHTML = "";
      for (var i = 0; i < aResults.length; i++) {
	var linkname = (aResults[i][0].length > 25) ? aResults[i][0].substr(0, 25) + "..." : aResults[i][0];
	urlField.innerHTML += '<a href="' + aResults[i][4] + '">' + linkname + '</a>';
	if (i < aResults.length - 1) { urlField.innerHTML += " "; }
      }
      urlField.style.display = "inline";
    }
  }
};
var DBFields_hideURL = function(sType, aArgs) {
  var oCompleter = aArgs[0];
  if (oCompleter.urlField) {
    var urlField = document.getElementById(oCompleter.urlField);
    if (urlField) {
      urlField.style.display = "none";
    }
  }
};
var DBFields_hideDefinition = function(sType, aArgs) {
  var oCompleter = aArgs[0];
  if (oCompleter.definitionBox) {
    oCompleter.definitionBox.style.display = "none";
  }
};

var DBFields_showDefinition = function(sType, aArgs) {
  var oCompleter = aArgs[0];
  var oContainer = oCompleter._oContainer; // Yikes, private property
  var elItem = aArgs[1];
  var data = oCompleter.getListItemData(elItem);

  var name = data[0];
  var cv = data[1];
  var accession = data[2];
  var definition = data[3];

  if (!oCompleter.definitionBox) { 
    var form = oContainer.form;
    var defDiv = document.createElement("div");
    defDiv.className = "definition";
    defDiv.innerHTML = "definition";
    defDiv.style.position = "relative";
    defDiv.style.left = '315px'; //findPos(oContainer)[0] + "px";
    defDiv.style.top = '-' + oCompleter._oTextbox.offsetHeight + 'px'; //findPos(oContainer)[1] + "px";
    defDiv.style.width = '300px';
    oCompleter.definitionBox = oContainer.appendChild(defDiv);
  } else {
    oCompleter.definitionBox.style.display = "block";
  }
  if (!definition) { definition = "No definition."; }
  oCompleter.definitionBox.innerHTML = accession + ": <b>" + name + "</b><br/>" + definition;
};

var DBFields_showSpinner = function(sType, aArgs) {
  var oCompleter = aArgs[0];
  var oContainer = oCompleter._oContainer; // Yikes, private property
  if (!oCompleter.spinnerBox) {
    var form = oContainer.form;
    var defDiv = document.createElement("div");
    defDiv.className = "spinner";
    defDiv.innerHTML = "<img class=\"spinner\" src=\"<?=dirname($_SERVER["PHP_SELF"]);?>/spinner.gif\" alt=\"loading...\"/>";
    defDiv.style.position = "relative";
    defDiv.style.left = '384px'; //findPos(oContainer)[0] + "px";
    defDiv.style.top = '-' + (2+oCompleter._oTextbox.offsetHeight) + 'px'; //findPos(oContainer)[1] + "px";
    oCompleter.spinnerBox = oContainer.appendChild(defDiv);
  }
  oCompleter.spinnerBox.style.display = "block";
}
var DBFields_hideSpinner = function(sType, aArgs) {
  var oCompleter = aArgs[0];
  var oContainer = oCompleter._oContainer; // Yikes, private property
  if (oCompleter.spinnerBox) {
    oCompleter.spinnerBox.style.display = "none";
  }
}


/************************************************************
* Set up the autocompletion widget for cvterm fields        *
************************************************************/
var autocompleters = [];
function DBFields_runOnLoad() {
    //var myLogReader = new YAHOO.widget.LogReader(); 
    var cvtermInputs = document.getElementsBySelector("input.cvterm");
    if (cvtermInputs) {
        for (i = 0; element = cvtermInputs[i]; i++) {
            var cv = element.getAttribute('cv');
            var multiple = element.getAttribute('multiple');
            if (!cv) { continue; }
            var url = "<?=dirname($_SERVER["PHP_SELF"]);?>/DBFieldsCVTerm.php"
	    var dataSource = new YAHOO.widget.DS_XHR(url, [ 'term', 'name', 'cv', 'accession', 'definition', 'url' ]);
            dataSource.responseType = YAHOO.widget.DS_XHR.TYPE_XML;
            dataSource.scriptQueryAppend = "cv=" + cv;
            dataSource.scriptQueryParam = "term";
            dataSource.queryMatchCase = true;
            dataSource.queryMatchContains = true;
            dataSource.maxCacheEntries = 60;
            dataSource.queryMatchSubset = false; 

            var input_id = element.id;
            var container_id = input_id + "_container";

            var autoComp = new YAHOO.widget.AutoComplete(input_id, container_id, dataSource);
            autoComp.minQueryLength = 1;
            if (multiple == "true") { 
                autoComp.delimChar = ','; 
                autoComp.extraSelectionKeycodes = 188;
                autoComp.forceSelectionDelayed = true; 
            } else { 
                autoComp.delimChar = ''; 
                autoComp.forceSelectionDelayed = true; 
            }
            autoComp.formatResult = function(aResultItem, sQuery) {
                var termName = aResultItem[0];
                var cvName = aResultItem[1];
                var accession = aResultItem[2];
		termName = (termName.length > 30) ? termName.substr(0, 30) + "..." : termName;
                termName = termName.replace(sQuery, "<span class=\"queryText\">" + sQuery + "</span>");
		if (termName.length = 0) { termName = "&nbsp;"; }
                var formattedResult =
                    "<div class=\"formattedResult\">" +
                    "<div class=\"accession\">" + accession + "</div>" +
                    "<div class=\"termName\">" + termName + "</div>" +
                    "</div>";
                return formattedResult;
            };

	    autoComp.itemArrowToEvent.subscribe(DBFields_showDefinition);
	    autoComp.itemMouseOverEvent.subscribe(DBFields_showDefinition);
	    autoComp.textboxBlurEvent.subscribe(DBFields_hideDefinition);
	    autoComp.itemSelectEvent.subscribe(DBFields_hideDefinition);
	    autoComp.dataRequestEvent.subscribe(DBFields_hideDefinition);
	    autoComp.dataRequestEvent.subscribe(DBFields_showSpinner);
	    autoComp.dataReturnEvent.subscribe(DBFields_hideSpinner);
	    autoComp.dataErrorEvent.subscribe(DBFields_hideSpinner);
	    autoComp.finishedValidatingEvent.subscribe(DBFields_hideSpinner);
	    autoComp.textboxFocusEvent.subscribe(DBFields_hideURL);
	    autoComp.finishedValidatingEvent.subscribe(DBFields_showURL);

            autoComp.allowBrowserAutocomplete = false;
	    autoComp.urlField = input_id + "_url";

            autocompleters[autocompleters.length] = autoComp;
        }
    }
}

onloadFuncts[onloadFuncts.length] = DBFields_runOnLoad;

