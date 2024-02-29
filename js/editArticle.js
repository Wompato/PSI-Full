// Function to generate a unique ID for the li element
function generateUniqueId() {
    return 'li_' + Math.random().toString(36).substr(2, 9);
}

// Function to perform the desired action when the target element is found
function handleTargetElement(targetElement, dropArea ,imageData, index) {
        
        dropArea.innerHTML = '';
        dropArea.classList.add('gpfup__droparea--maxed');

        // Create a new div element with a child span element
        let maxFilesReachedDiv = document.createElement('div');
        let spanElement = document.createElement('span');
        spanElement.textContent = 'Maximum number of files reached';
        maxFilesReachedDiv.appendChild(spanElement);

        // Append the new HTML to dropArea
        dropArea.appendChild(maxFilesReachedDiv); 

        renderImagePreview(targetElement, dropArea, imageData, index);
    
}

function renderImagePreview(targetElement, dropArea, imageData, index) {
    
    if(index === undefined) {
        index = 'featured-image';
    }

    var featuredImageUrl = imageData[1];
    var ulElement = document.createElement('ul');
    ulElement.className = 'gpfup__files';

    // Append the ul element as the first child of the target element
    targetElement.insertBefore(ulElement, targetElement.firstChild);

    // Create a new li element with the specified attributes
    var liElement = document.createElement('li');
    liElement.setAttribute('data-file-id', generateUniqueId());
    liElement.setAttribute('data-file-type', 'image/jpeg');
    liElement.setAttribute('data-file-ext', 'jpg');
    liElement.className = 'gpfup__file';

    // Append the li element to the ul element
    ulElement.appendChild(liElement);

    // Add input element to add image url to the $_POST variable in php backend
    let inputEl = document.createElement('input');
    inputEl.setAttribute('type', 'text');
    inputEl.name = 'imageInput-' + index;
    inputEl.value = featuredImageUrl;
    inputEl.style.display = 'none';
    liElement.appendChild(inputEl);

    // Create one div element inside the li element with class gpfup__preview
    var divElement = document.createElement('div');
    divElement.className = 'gpfup__preview'; // Set the class for the div
    liElement.appendChild(divElement); // Append the div to the li element

    // Create an img element inside the div with the class gpfup__preview
    
    var imgElement = document.createElement('img');
    imgElement.src = featuredImageUrl; 
    imgElement.alt = 'Image Preview';
    divElement.appendChild(imgElement); 

    // Create a second div element inside the li element with class gpfup__file-info
    var fileInfoDiv = document.createElement('div');
    fileInfoDiv.className = 'gpfup__file-info';
    liElement.appendChild(fileInfoDiv);

    // Create additional div elements inside gpfup__file-info
    var filenameDiv = document.createElement('div');
    filenameDiv.className = 'gpfup__filename';
    filenameDiv.textContent = imageData[2] ? imageData[2] : imageData[3];
    fileInfoDiv.appendChild(filenameDiv);

    var filesizeDiv = document.createElement('div');
    filesizeDiv.className = 'gpfup__filesize';
    filesizeDiv.textContent = imageData[4];
    fileInfoDiv.appendChild(filesizeDiv);

    const elements = [ulElement, dropArea, targetElement, featuredImageUrl];

    // Create a third div element inside the li element with class gpfup__file-actions
    var fileActionsDiv = document.createElement('div');
    fileActionsDiv.className = 'gpfup__file-actions';
    fileActionsDiv.innerHTML = '<button data-gpfup-filename="" class="gpfup__delete gform-theme-no-framework"><svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 16 16" role="img" aria-hidden="true" focusable="false"><path d="M11.55,1.65,7.42,5.78,11.55,9.9,9.9,11.55,5.77,7.43,1.66,11.55,0,9.89,4.12,5.78,0,1.66,1.66,0,5.77,4.12,9.9,0Z"></path></svg></button>';
    liElement.appendChild(fileActionsDiv);

    fileActionsDiv.addEventListener('click', function(e){
        removeImagePreview(e, elements);
    })

}


// Function to handle selected file and create corresponding elements
function handleSelectedFile(targetElement, selectedFile, dropArea, index) {

    

    if(index === undefined) {
        index = 'featured-image';
    }
    
    var ulElement = document.createElement('ul');
    ulElement.className = 'gpfup__files';

    // Append the ul element as the first child of the target element
    targetElement.insertBefore(ulElement, targetElement.firstChild);

    // Create a new li element with the specified attributes
    var liElement = document.createElement('li');
    liElement.setAttribute('data-file-id', generateUniqueId());
    liElement.setAttribute('data-file-type', selectedFile.type);
    liElement.setAttribute('data-file-ext', getFileExtension(selectedFile.name));
    liElement.className = 'gpfup__file';

    // Append the li element to the ul element
    ulElement.appendChild(liElement);

    // Create one div element inside the li element with class gpfup__preview
    var divElement = document.createElement('div');
    divElement.className = 'gpfup__preview'; // Set the class for the div
    liElement.appendChild(divElement); // Append the div to the li element

    // Read the selected file and convert it to base64
    var reader = new FileReader();
    reader.onload = function(e) {
        // Create an img element inside the div with the class gpfup__preview
        var imgElement = document.createElement('img');
        imgElement.src = e.target.result;
        imgElement.alt = 'Image Preview';
        divElement.appendChild(imgElement); // Append the img to the div

        // Add input element to add image url to the $_POST variable in php backend
        /* let inputEl = document.createElement('input');
        inputEl.setAttribute('type', 'text');
        inputEl.name = 'imageInput-' + index;
        console.log(selectedFile)
        inputEl.value = e.target.result;
        inputEl.style.display = 'none';
        liElement.appendChild(inputEl); */
    };
    reader.readAsDataURL(selectedFile);

    // Create a second div element inside the li element with class gpfup__file-info
    var fileInfoDiv = document.createElement('div');
    fileInfoDiv.className = 'gpfup__file-info';
    liElement.appendChild(fileInfoDiv);

    // Create additional div elements inside gpfup__file-info with metadata
    var filenameDiv = document.createElement('div');
    filenameDiv.className = 'gpfup__filename';
    filenameDiv.textContent = selectedFile.name;
    fileInfoDiv.appendChild(filenameDiv);

    var filesizeDiv = document.createElement('div');
    filesizeDiv.className = 'gpfup__filesize';
    filesizeDiv.textContent = formatBytes(selectedFile.size);
    fileInfoDiv.appendChild(filesizeDiv);

    dropArea.removeChild(dropArea.firstChild);
   
        dropArea.classList.add('gpfup__droparea--maxed');

        // Create a new div element with a child span element
        let maxFilesReachedDiv = document.createElement('div');
        let spanElement = document.createElement('span');
        spanElement.textContent = 'Maximum number of files reached';
        maxFilesReachedDiv.appendChild(spanElement);

        // Append the new HTML to dropArea
        dropArea.appendChild(maxFilesReachedDiv);   

    const elements = [ulElement, dropArea, targetElement, selectedFile.name];

    // Create a third div element inside the li element with class gpfup__file-actions
    var fileActionsDiv = document.createElement('div');
    fileActionsDiv.className = 'gpfup__file-actions';
    fileActionsDiv.innerHTML = '<button data-gpfup-filename="zach-bain.jpg" class="gpfup__delete gform-theme-no-framework"><svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 16 16" role="img" aria-hidden="true" focusable="false"><path d="M11.55,1.65,7.42,5.78,11.55,9.9,9.9,11.55,5.77,7.43,1.66,11.55,0,9.89,4.12,5.78,0,1.66,1.66,0,5.77,4.12,9.9,0Z"></path></svg></button>';
    liElement.appendChild(fileActionsDiv);

    fileActionsDiv.addEventListener('click', function(e){
        removeImagePreview(e, elements);
    })
}

function removeImagePreview(e, elements) {
    if(e){
        e.preventDefault();
    }
    
    let [ulElement, dropArea, targetElement, selectedFile] = elements;

    let match = targetElement.parentElement.id.match(/(\d+)$/);
    
    // Handle the removal of elements
    if(ulElement){
        ulElement.remove(); // Remove the entire li element
    }
    
    dropArea.innerHTML = ''; // Clear the content of dropArea
    dropArea.classList.remove('gpfup__droparea--maxed'); // Remove the class from dropArea
    // Add new HTML content to dropArea
    dropArea.innerHTML = '<div><span>Drop files here or &nbsp;</span><span class="gpfup__select-files-container"><button type="button" class="gpfup__select-files gform_button_select_files">select files</button></span></div>';

    // Create a file input element
    var fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.name = `input_${match[0]}`;
    fileInput.style.display = 'none'; // Make it invisible

    dropArea.appendChild(fileInput);

    targetElement.nextElementSibling.value = '';
    

    // Add an event listener to the file input element to handle selected files
    fileInput.addEventListener('change', function () {
        // Access the selected files using fileInput.files
        var selectedFiles = fileInput.files;

        // For simplicity, let's assume the user selects only one file
        var selectedFile = selectedFiles[0];

        // Call the handleSelectedFile function with the targetElement and selectedFile
        handleSelectedFile(targetElement, selectedFile, dropArea);

    });

    // Add an event listener to the select files button
    var selectFilesButton = document.querySelector('.gpfup__select-files');
    selectFilesButton.addEventListener('click', function() {
        // Trigger the click event on the file input element
        fileInput.click();
    });

    
};


// Function to get the file extension from the filename
function getFileExtension(filename) {
    return filename.slice((filename.lastIndexOf(".") - 1 >>> 0) + 2);
}

// Function to format file size in a human-readable format
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}


jQuery(document).ready(function($) {
    let selectElement = $('#input_14_1');
    let dateElement = $('#input_14_13');
    let timeElement = $('#input_14_14');

    let postSelectDropdown = $('#input_14_1-ts-dropdown');

    
    const currentUrl = window.location.href;

    // Extract the query string from the URL
    const queryString = currentUrl.split('?')[1];

    // Create a URLSearchParams object from the query string
    const urlSearchParams = new URLSearchParams(queryString);

    if (urlSearchParams.has('article-name')) {
        // Get the value of the project-name parameter
        const articleName = urlSearchParams.get('article-name');

        // Make a request to your custom endpoint
        $.ajax({
            url: '/wp-json/psi/v1/post/' + articleName,
            method: 'GET',
            success: function(response) {
               
                // Access the featured image URL from the response
                let featuredImageData = response.featured_image;
                let date = response.post_date;
                let time = response.post_time;      

                let attachmentImages = response.additional_images;

            

                dateElement.val(date);
                const timeParts = time.match(/(\d+):(\d+) ([APMapm]{2})/);


                // Update hour, minute, and AM/PM fields
                $('#input_14_14_1').val(parseInt(timeParts[1], 10));
                $('#input_14_14_2').val(parseInt(timeParts[2], 10));
                $('#input_14_14_3').val(timeParts[3].toLowerCase());

                let targetElement = document.querySelectorAll('.gpfup');

                let uploadedFiles = document.querySelectorAll('.gpfup__files');

                let dropAreas = document.querySelectorAll('.gpfup__droparea');

               
                if(featuredImageData){
                    const { ID, url, title, filename, filesize, caption } = featuredImageData;
            
                    const imageData = [ID, url, title, filename, filesize, caption];

                    
                    
                    if(uploadedFiles[0]){
                        uploadedFiles[0].remove();
                    }
                    
                    if(url){
                        let captionEl = document.querySelector('#captionInput0');
                        captionEl.value = caption;
                        handleTargetElement(targetElement[0], dropAreas[0], imageData);
                    } else {
                        // add message to user here later
                        console.log('No Featured Image Found!');
                    }
                       
                }

                if(attachmentImages) {
                    
                    for(let i = 0; i < attachmentImages.length; i++) {
                        const { ID, url, title, filename, filesize, caption } = attachmentImages[i];

                        
                
                        const imageData = [ID, url, title, filename, filesize, caption];

                        if(uploadedFiles[i + 1]){
                            uploadedFiles[i + 1].remove();
                        }

                        if(url){
                            let captionEl = document.querySelector('#captionInput' + (i + 1));
                            
                            captionEl.value = caption;
                            handleTargetElement(targetElement[i + 1], dropAreas[i + 1], imageData, i);
                        } else {
                            let elements = ['', dropAreas[i + 1], targetElement[i + 1], ''];
                       
                            console.log('No Featured Image Found!');
                            removeImagePreview('', elements);
                        }
                    }
                }

                // You can do further processing or UI updates here
            },
            error: function(error) {
                // Handle errors here
                console.error('API Error:', error);
            }
        });

    }

    // Add an event listener for the 'change' event
    selectElement.on('change', function() {
        // Get the selected value
        let selectedValue = $(this).val();

        // Make a request to your custom endpoint
        $.ajax({
            url: '/wp-json/psi/v1/post/' + selectedValue,
            method: 'GET',
            success: function(response) {
               
                // Access the featured image URL from the response
                let featuredImageData = response.featured_image;
                let date = response.post_date;
                let time = response.post_time;

                let attachmentImages = response.attachment_images;

                dateElement.val(date);
                const timeParts = time.match(/(\d+):(\d+) ([APMapm]{2})/);

                // Update hour, minute, and AM/PM fields
                $('#input_14_14_1').val(parseInt(timeParts[1], 10));
                $('#input_14_14_2').val(parseInt(timeParts[2], 10));
                $('#input_14_14_3').val(timeParts[3].toLowerCase());

                let targetElement = document.querySelectorAll('.gpfup');

                let uploadedFiles = document.querySelectorAll('.gpfup__files');

                let dropAreas = document.querySelectorAll('.gpfup__droparea');

                
               
                if(featuredImageData){
                    const { ID, url, title, filename, filesize, caption } = featuredImageData;
            
                    const imageData = [ID, url, title, filename, filesize, caption];

                    
                    
                    if(uploadedFiles[0]){
                        uploadedFiles[0].remove();
                    }
                    
                    if(url){
                        let captionEl = document.querySelector('#captionInput0');
                        captionEl.value = caption;
                        handleTargetElement(targetElement[0], dropAreas[0], imageData);
                    } else {
                        // add message to user here later
                        let elements = ['', dropAreas[0], targetElement[0], ''];
                       
                        console.log('No Featured Image Found!');
                        removeImagePreview('', elements);
                    }
                       
                }

                if(attachmentImages) {
                    for(let i = 0; i < attachmentImages.length; i++) {
                        const { ID, url, title, filename, filesize, caption } = attachmentImages[i];

                        
                
                        const imageData = [ID, url, title, filename, filesize, caption];

                        

                        if(uploadedFiles[i + 1]){
                            uploadedFiles[i + 1].remove();
                        }

                        if(url){
                            let captionEl = document.querySelector('#captionInput' + (i + 1));
                            
                            captionEl.value = caption;
                            handleTargetElement(targetElement[i + 1], dropAreas[i], imageData);
                        } else {
                            // add message to user here later
                            console.log('No Additional Image Found!');
                        }
                    }
                }

                // You can do further processing or UI updates here
            },
            error: function(error) {
                // Handle errors here
                console.error('API Error:', error);
            }
        });
    });
});

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
        placeholder: 'Enter caption'
    });

    inputTextElement.css('margin-top', '10px'); 
    inputTextElement.css('min-width', '100%'); 

    return inputTextElement;
}

function removeFileFromGFUploadedMeta(formId, fieldId, file) {
    var filesJsonElement = document.getElementById('gform_uploaded_files_' + formId);
 
    if (filesJsonElement) {
        var filesJson = filesJsonElement.value;
        if (filesJson) {
            var files = JSON.parse(filesJson);
            if (files) {
                var inputName = "input_" + fieldId;
                var multfileElement = document.getElementById("gform_multifile_upload_" + formId + "_" + fieldId);

                if (multfileElement && files[inputName]) {
                    files[inputName] = files[inputName].filter(function (hiddenFileMeta) {
                        return (
                            hiddenFileMeta?.temp_filename?.indexOf(file.id) < 0
                            && hiddenFileMeta?.uploaded_filename !== file.id
                        );
                    });

                    var settings = JSON.parse(multfileElement.getAttribute('data-settings'));
                    var max = settings.gf_vars.max_files;
                    var messageElement = document.getElementById(settings.gf_vars.message_id);

                    if (messageElement) {
                        messageElement.innerHTML = '';
                    }

                    if (files[inputName].length < max) {
                        window.gfMultiFileUploader.toggleDisabled(settings, false);
                    }
                } else {
                    files[inputName] = [];
                }

                filesJsonElement.value = JSON.stringify(files);
            }
        }
    }
}
