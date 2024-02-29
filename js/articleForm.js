jQuery(document).ready(function($) {
    let multUpFields = $('.gform_fileupload_multifile');

    multUpFields.each(function(index) {
        let el = createCaptionEl(index);
        $(this).append(el);
    });
});

function createCaptionEl(index) {
    let inputTextElement = jQuery('<input>', {
        type: 'text',
        id: 'captionInput' + index,
        name: 'captionInput' + index,
        placeholder: 'Enter caption' // Optionally, display the index in the placeholder
        // value: 'Default Value'
    });

    inputTextElement.css('margin-top', '10px'); // Use jQuery for consistency

    return inputTextElement;
}
