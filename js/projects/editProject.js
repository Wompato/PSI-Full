jQuery(document).ready(function($) {
    jQuery(document).ready(function($) {
    
        const currentUrl = window.location.href;

        // Extract the query string from the URL
        const queryString = currentUrl.split('?')[1];

        // Create a URLSearchParams object from the query string
        const urlSearchParams = new URLSearchParams(queryString);

        if (urlSearchParams.has('project-name')) {
            // Get the value of the project-name parameter
            const projectName = urlSearchParams.get('project-name');

            $.ajax({
                url: '/wp-json/psi/v1/project/' + projectName,
                type: 'GET',
                success: function(response) {
                    let featuredImageData = response.featured_image;
                    if(featuredImageData){
                        const { id, url, title, filename , filesize } = featuredImageData;
                    
                        const imageData = [id, url, title, filename ,filesize];
    
                        let targetElement = document.querySelector('.gpfup');
    
                        uploadedFiles = document.querySelector('.gpfup__files');
                        
                        if(uploadedFiles){
                            uploadedFiles.remove();
                        }
                        
                        if(url){
                            handleTargetElement(targetElement, imageData);
                        } else {
                            // add message to user here later
                            
                        }
                           
                    }      
        
                },
                error: function(error) {
                    console.error("API Error:", error);
                }
            });
        }
    });
    
    
    // Function to generate a unique ID for the li element
    function generateUniqueId() {
        return 'li_' + Math.random().toString(36).substr(2, 9);
    }
    
    // Function to perform the desired action when the target element is found
    function handleTargetElement(targetElement, imageData) {
        
        let dropArea = document.querySelector('.gpfup__droparea');
    
      // Clear the content of dropArea
        dropArea.innerHTML = '';
    
        // Add the class to dropArea
        dropArea.classList.add('gpfup__droparea--maxed');
    
        // Create a new div element with a child span element
        let maxFilesReachedDiv = document.createElement('div');
        let spanElement = document.createElement('span');
        spanElement.textContent = 'Maximum number of files reached';
        maxFilesReachedDiv.appendChild(spanElement);
    
        // Append the new HTML to dropArea
        dropArea.appendChild(maxFilesReachedDiv); 
    
        renderImagePreview(targetElement, dropArea, imageData);
        
    }
    
    function renderImagePreview(targetElement, dropArea, imageData) {
        // Create a new ul element with the specified class
        let featuredImageUrl = imageData[1];
        let ulElement = document.createElement('ul');
        ulElement.className = 'gpfup__files';
    
        // Append the ul element as the first child of the target element
        targetElement.insertBefore(ulElement, targetElement.firstChild);
    
        // Create a new li element with the specified attributes
        let liElement = document.createElement('li');
        liElement.setAttribute('data-file-id', generateUniqueId());
        liElement.setAttribute('data-file-type', 'image/jpeg');
        liElement.setAttribute('data-file-ext', 'jpg');
        liElement.className = 'gpfup__file';
    
        // Append the li element to the ul element
        ulElement.appendChild(liElement);

        // Add input element to add image url to the $_POST variable in php backend
        let inputEl = document.createElement('input');
        inputEl.setAttribute('type', 'text');
        inputEl.name = 'featured_image';
        inputEl.value = featuredImageUrl;
        inputEl.style.display = 'none';
        liElement.appendChild(inputEl);
    
        // Create one div element inside the li element with class gpfup__preview
        let divElement = document.createElement('div');
        divElement.className = 'gpfup__preview'; // Set the class for the div
        liElement.appendChild(divElement); // Append the div to the li element
    
        // Create an img element inside the div with the class gpfup__preview
        let imgElement = document.createElement('img');
        imgElement.src = featuredImageUrl; // Replace BASE64_IMAGE_DATA_HERE with your actual base64 image data
        imgElement.alt = 'Image Preview';
        divElement.appendChild(imgElement); // Append the img to the div
    
        // Create a second div element inside the li element with class gpfup__file-info
        let fileInfoDiv = document.createElement('div');
        fileInfoDiv.className = 'gpfup__file-info';
        liElement.appendChild(fileInfoDiv);
    
        // Create additional div elements inside gpfup__file-info
        let filenameDiv = document.createElement('div');
        filenameDiv.className = 'gpfup__filename';
        filenameDiv.textContent = imageData[2] ? imageData[2] : imageData[3];
        fileInfoDiv.appendChild(filenameDiv);
    
        let filesizeDiv = document.createElement('div');
        filesizeDiv.className = 'gpfup__filesize';
        filesizeDiv.textContent = imageData[4];
        fileInfoDiv.appendChild(filesizeDiv);
    
        const elements = [ulElement, dropArea, targetElement];
    
        // Create a third div element inside the li element with class gpfup__file-actions
        let fileActionsDiv = document.createElement('div');
        fileActionsDiv.className = 'gpfup__file-actions';
        fileActionsDiv.innerHTML = '<button data-gpfup-filename="zach-bain.jpg" class="gpfup__delete gform-theme-no-framework"><svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="-2 -2 16 16" role="img" aria-hidden="true" focusable="false"><path d="M11.55,1.65,7.42,5.78,11.55,9.9,9.9,11.55,5.77,7.43,1.66,11.55,0,9.89,4.12,5.78,0,1.66,1.66,0,5.77,4.12,9.9,0Z"></path></svg></button>';
        liElement.appendChild(fileActionsDiv);
    
        fileActionsDiv.addEventListener('click', function(e){
            removeImagePreview(e, elements);
        })
    
    }
    
    
    // Function to handle selected file and create corresponding elements
    function handleSelectedFile(targetElement, selectedFile, dropArea, fileInput) {
        // Create a new ul element with the specified class
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
    
        const elements = [ulElement, dropArea, targetElement, fileInput];
    
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
        e.preventDefault();
        let [ulElement, dropArea, targetElement] = elements;
        // Handle the removal of elements
        ulElement.remove(); // Remove the entire li element
        dropArea.innerHTML = ''; // Clear the content of dropArea
        dropArea.classList.remove('gpfup__droparea--maxed'); // Remove the class from dropArea
        // Add new HTML content to dropArea
        dropArea.innerHTML = '<div><span>Drop files here or &nbsp;</span><span class="gpfup__select-files-container"><button type="button" class="gpfup__select-files gform_button_select_files">select files</button></span></div>';
    
        // Create a file input element
        let fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.name = 'input_21';
        fileInput.style.display = 'none'; // Make it invisible
    
        dropArea.appendChild(fileInput);
    
        // Add an event listener to the file input element to handle selected files
        fileInput.addEventListener('change', function () {
            // Access the selected files using fileInput.files
            let selectedFiles = fileInput.files;
    
            // For simplicity, let's assume the user selects only one file
            let selectedFile = selectedFiles[0];
    
            let dropArea = document.querySelector('.gpfup__droparea');
    
        
            //dropArea.innerHTML = '';
        
            // Add the class to dropArea
            /* dropArea.classList.add('gpfup__droparea--maxed');
        
            // Create a new div element with a child span element
            let maxFilesReachedDiv = document.createElement('div');
            let spanElement = document.createElement('span');
            spanElement.textContent = 'Maximum number of files reached';
            maxFilesReachedDiv.appendChild(spanElement);
        
            // Append the new HTML to dropArea
            dropArea.appendChild(maxFilesReachedDiv);  */

            handleSelectedFile(targetElement, selectedFile, dropArea, fileInput);
    
        });
    
        // Add an event listener to the select files button
        let selectFilesButton = document.querySelector('.gpfup__select-files');
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
        let selectElement = $('#input_8_1');
    
        // Add an event listener for the 'change' event
        selectElement.on('change', function() {
            // Get the selected value
            let selectedValue = $(this).val();
            
    
            // Make a request to your custom endpoint
            $.ajax({
                url: '/wp-json/psi/v1/project/' + selectedValue,
                method: 'GET',
                success: function(response) {
                    
                    // Access the featured image URL from the response
                    
                    let featuredImageData = response.featured_image;
                    if(featuredImageData){
                        const { id, url, title, filename , filesize } = featuredImageData;
                    
                        const imageData = [id, url, title, filename ,filesize];
    
                        let targetElement = document.querySelector('.gpfup');
    
                        uploadedFiles = document.querySelector('.gpfup__files');
                        
                        if(uploadedFiles){
                            uploadedFiles.remove();
                        }
                        
                        if(url){
                            handleTargetElement(targetElement, imageData);
                        } else {
                            // add message to user here later
                            
                        }
                           
                    }
    
                },
                error: function(error) {
                    // Handle errors here
                    console.error('API Error:', error);
                }
            });
        });
    });
    
});