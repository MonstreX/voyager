var ace_editor_element = document.getElementsByClassName("ace_editor");

// For each ace editor element on the page
for(var i = 0; i < ace_editor_element.length; i++)
{
    if (typeof window === 'undefined' || typeof window.ace === 'undefined') {
        continue;
    }
    var aceGlobal = window.ace;

    //Define path for libs
    const assetsMeta = document.querySelector('meta[name="assets-path"]');
    const assetsBase = assetsMeta ? assetsMeta.getAttribute('content') : '';
    const aceBaseMeta = document.querySelector('meta[name="voyager-ace-base"]');
    const explicitAceBase = window.voyagerAceBase || (aceBaseMeta ? aceBaseMeta.getAttribute('content') : '') || '';
    const fallbackBase = assetsBase.replace(/\/?$/, '/') + 'js/ace/libs';
    const aceBasePath = (explicitAceBase || fallbackBase).replace(/\/?$/, '/');
    aceGlobal.config.set("basePath", aceBasePath);
    aceGlobal.config.set("workerPath", aceBasePath);
    aceGlobal.config.set("modePath", aceBasePath);
    aceGlobal.config.set("themePath", aceBasePath);

	// Create an ace editor instance
	var ace_editor = aceGlobal.edit(ace_editor_element[i].id);

	// Get the corresponding text area associated with the ace editor
	var ace_editor_textarea = document.getElementById(ace_editor_element[i].id + '_textarea');

    if(ace_editor_element[i].getAttribute('data-theme')){
    	ace_editor.setTheme("ace/theme/" + ace_editor_element[i].getAttribute('data-theme'));
    }

    if(ace_editor_element[i].getAttribute('data-language')){
    	ace_editor.getSession().setMode("ace/mode/" + ace_editor_element[i].getAttribute('data-language'));
    }
    
    ace_editor.on('change', function(event, el) {
        var ace_editor_id = el.container.id;
        var ace_editor_textarea_local = document.getElementById(ace_editor_id + '_textarea');
        var ace_editor_instance = aceGlobal.edit(ace_editor_id);
        if (ace_editor_textarea_local) {
            ace_editor_textarea_local.value = ace_editor_instance.getValue();
        }
    });
}
