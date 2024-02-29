<?php

require_once get_stylesheet_directory() . '/psi-api/RestEndpoints.php';
require_once get_stylesheet_directory() . '/forms/FormHandler.php';
require_once get_stylesheet_directory() . '/GW_Settings/settings.php';

class PSI_Child_Theme {
    public function __construct() {
        // Role management
        add_action('init', array($this, 'add_custom_roles'));

        // ACF filters setup
        add_action('init', array($this, 'setup_acf_filters'));

        // Restrict access to listed forms by user role
        add_filter('gform_pre_render', array($this, 'restrict_form_access'));  

        // Single Post Pages get the grid of posts as the bottom.
        add_filter('the_content', array($this, 'display_additional_images_in_single_post'));

        // Register the REST API endpoint
        add_action('rest_api_init', array('RestClass', 'register_endpoints'));

        // Modify attachments query so non admins can only access media files that they upload
        add_filter('ajax_query_attachments_args', array($this, 'show_current_user_attachments'));

        // Custom rewrite rules
        add_action('init', array($this, 'custom_rewrite_rules'));

        add_action('gform_after_submission_11', array($this, 'create_staff_page'), 20, 2);
        add_action('gform_after_submission_12', array($this, 'update_staff_page'), 20, 2);
       
        add_action('gform_after_submission_10', array($this, 'create_project'), 10, 2);
        add_action('gform_after_submission_8',  array($this, 'update_project'), 10, 2);

        add_action('gform_after_submission_13', array($this, 'create_article'), 10, 2);
        
        // 14
        add_action('gform_after_submission_9', array($this, 'update_article'), 10, 2);

        // Add excerpt to Gravity Forms Advanced Post Creation
        add_filter( 'gform_advancedpostcreation_excerpt', function( $enable_excerpt ) {
            return true;
        }, 10, 1 );

        // for the edit article form, this allows for 1000 posts to be queries by the edit form.
        add_filter( 'gppa_query_limit_14_1', function( $query_limit, $object_type ) {
            // Update "1000" to the maximum number of results that should be returned for the query populating this field.
            return 1000;
        }, 10, 2 );

        // for the create project form, this allows for 1000 posts to be queries by the related CS/PR section.
        add_filter( 'gppa_query_limit_10_22', function( $query_limit, $object_type ) {
            // Update "1000" to the maximum number of results that should be returned for the query populating this field.
            return 1000;
        }, 10, 2 );

        add_action('acf/save_post', array($this, 'update_user_fields'), 30);
        add_filter('acf/fields/relationship/query', array($this, 'sort_relationship_field_by_date'), 10, 3);

        // Custom query vars
        add_filter('query_vars', array($this, 'custom_query_vars'));

        // Modify menu items
        add_filter('wp_nav_menu_items', array($this, 'modify_menu_items'), 10, 2);  

        // Handle conflict of Select2 library
        add_action('admin_enqueue_scripts', array($this, 'enqueue_acf_select2_and_dequeue_wcd_select2_admin'), 99);

        // Enqueue custom scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_custom_scripts'));

        // Enqueue Slick.js from CDN
        add_action('wp_enqueue_scripts', array($this, 'enqueue_slick_from_cdn'));

        // Register the related_users shortcode
        add_shortcode('related_users', array($this, 'related_users_shortcode'));
        add_shortcode('load_more_posts', array($this, 'load_more_posts_shortcode'));
        add_shortcode('edit_article_link', array($this, 'edit_article_link_shortcode'));

        add_filter('login_redirect', array($this, 'redirect_non_admin_users'), 10, 3);

        // Hide admin bar for non-administrator users
        if (!current_user_can('administrator')) {
            add_filter('show_admin_bar', '__return_false');
        }
    }

    

    public function display_additional_images_in_single_post($content) {
        global $post;
    
        // Check if it's a single post
        if (is_single() && $post) {
            $additional_images = get_field('additional_images', $post->ID);
    
            if ($additional_images && is_array($additional_images)) {
                $image_html = '<div class="image-grid">';
    
                foreach ($additional_images as $attachment_id) {
                    $image_html .= '<div class="grid-item">';
                    $image_html .= wp_get_attachment_image($attachment_id, 'medium'); // Display the image with 'medium' size
                    $image_html .= '<p>' . esc_html(get_post_field('post_excerpt', $attachment_id)) . '</p>'; // Display the caption
                    $image_html .= '</div>';
                }
    
                $image_html .= '</div>';
    
                // Append the additional images to the post content
                $content .= $image_html;
            }
        }
    
        return $content;
    }

    public function restrict_form_access($form) {
       
        if($form['id'] != 8){
            return $form;
        }

        $user = wp_get_current_user();
      
        $user_id = $user->ID;
        $roles = (array) $user->roles;
    
        if (empty($roles)) {
            wp_redirect( home_url() );
            exit;
        }

        $role = $roles[0]; // Assuming the user has only one role

        // Allow staff member editors and administrators to access the form
        if ( $role == 'staff_member_editor' || $role == 'administrator' ) {
            return $form;
        }

        if ( !isset( $_GET['project-name'] ) && ($role != 'staff_member_editor' || $role != 'administrator')) {
            wp_redirect( home_url() );
            exit;
        } 

        $project_id = $_GET['project-name'];
        $principal_investigator = get_field('primary_investigator', $project_id);

        if ( $principal_investigator && $user_id != $principal_investigator->ID ) {
            wp_redirect( home_url() );
            exit;
        }

        return $form;
    }

    public function redirect_non_admin_users($redirect_to, $request, $user) {
        // Is there a user?
        if (isset($user->roles) && is_array($user->roles)) {
            // Is the user not an administrator?
            if (!in_array('administrator', $user->roles)) {
                // Redirect to the front end
                return home_url();
            }
        }
    
        // If user is an administrator or there is no user role information, proceed with the default redirect
        return $redirect_to;
    }
    

    public function sort_relationship_field_by_date($args, $field, $post_id) {
        // Check if we are on the correct field
        if ($field['name'] == 'related_posts') {
            // Modify the query to order by post date
            $args['orderby'] = 'date';
            $args['order'] = 'DESC'; 
        }
    
        return $args;
    }

    public function update_article($entry, $form) {
        // Get the post ID you want to update
        $post_id = rgar($entry, '15');
        $title = rgar($entry, '3');
        $content = rgar($entry, '4');
        $excerpt = rgar($entry, '5');
        $tags = rgar($entry, '6');
        $featured_image = rgar($entry, '12');
        $related_staff = rgar($entry, '10');
        $related_projects = rgar($entry, '16');
        $date = rgar($entry, '13');
        $time = rgar($entry, '14');

        $category_name = rgar($entry, '23');
        $category = get_term_by( 'name', $category_name, 'category' );
        $category_id = $category->term_id;
        
        // Convert date from "mm/dd/yyyy" to "Y-m-d"
        $converted_date = !empty($date) ? new DateTime($date) : new DateTime(); // Use current date if empty
        $format_date = $converted_date->format('Y-m-d');

        // Convert time from "hh/mm/(am or pm)" to "H:i:s"
        $converted_time = !empty($time) ? new DateTime($time) : new DateTime(); // Use current time if empty
        $format_time = $converted_time->format('H:i:s');

        // Set post_date to current date and time if date and/or time is empty
        $scheduled_date = new DateTime(); // Initialize $scheduled_date here

        if (!empty($date) && !empty($time)) {
            $post_date = $format_date . ' ' . $format_time;

            // Check if the scheduled date is in the future
            $scheduled_date = new DateTime($post_date, new DateTimeZone(get_option('timezone_string')));
            $current_date = new DateTime(null, new DateTimeZone(get_option('timezone_string')));
            if ($scheduled_date > $current_date) {
                // Set post_status to 'future' if the date is in the future
                $post_status = 'future';
            } else {
                // Set post_status to 'publish' if the date is in the past or present
                $post_status = 'publish';
            }
        } else {
            $wp_timezone = get_option('timezone_string');
            $current_date = new DateTime(null, new DateTimeZone($wp_timezone));
            $post_date = $current_date->format('Y-m-d H:i:s');
            $post_status = 'publish';
        }

        // Set post_date_gmt to be the same as post_date
        $post_date_gmt = $post_date;
        
        $post_data = array(
            'ID'           => $post_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => $post_status,
            'post_date'    => $post_date,
            'post_date_gmt' => $post_date_gmt,
        );

        // Update the post
        wp_update_post($post_data);

        $image_captions = []; // Array to store captions
        $uploaded_attachment_ids = array(); // Array to store uploaded attachment IDs

        // For each caption add it to the image captions array
        for ($i = 0; isset($_POST['captionInput' . $i]); $i++) {
            $image_captions[] = sanitize_text_field($_POST['captionInput' . $i]);
        }

        // Data for the file upload fields
        $additional_images_data = array(
            "imageInput-featured-image" => array(
                "input_key" => '12',
                "caption_key" => "captionInput0"
            ),
            "imageInput-0" => array(
                "input_key" => '17',
                "caption_key" => "captionInput1"
            ),
            "imageInput-1" => array(
                "input_key" => '18',
                "caption_key" => "captionInput2"
            ),
            "imageInput-2" => array(
                "input_key" => '19',
                "caption_key" => "captionInput3"
            ),
            "imageInput-3" => array(
                "input_key" => '20',
                "caption_key" => "captionInput4"
            ),
            "imageInput-4" => array(
                "input_key" => '21',
                "caption_key" => "captionInput5"
            ),
            "imageInput-5" => array(
                "input_key" => '22',
                "caption_key" => "captionInput6"
            )
        );

        // Based on if the file is handled through gforms, exists already in the $_POST array or is a new file input that is created 
        // by the forms javascript (exists in $_FILES array), handle the file
        foreach ($additional_images_data as $image_key => $data) {
            $input_key = $data['input_key'];
            $caption_key = $data['caption_key'];
            
            // Check if the input key exists in $_FILES (New upload from the input created by the forms JS)
            if (isset($_FILES["input_$input_key"]) && !empty($_FILES["input_$input_key"]['name'])) {
                // Handle the image upload from $_FILES
                $file_name = $_FILES["input_$input_key"]['name'];
                $attachment_id = $this->handle_uploaded_image($_FILES["input_$input_key"]['tmp_name'], $file_name, $_POST[$caption_key]);
                
                // If it's the featured image, set it as the post thumbnail
                if ($input_key === '12') {
                    set_post_thumbnail($post_id, $attachment_id);
                } else {
                    // Add the attachment ID to the uploaded-attachment-ids array
                    $uploaded_attachment_ids[] = $attachment_id;
                }
        
                // Update the caption of the image
                wp_update_post(array(
                    'ID'           => $attachment_id,
                    'post_excerpt' => $_POST[$caption_key],
                    'post_parent'  => $post_id,
                ));
            // File already exists, don't need to upload it
            } elseif (isset($_POST[$image_key])) {
                // Handle the image upload from $_POST
                $attachment_id = attachment_url_to_postid($_POST[$image_key]);
                
                // If it's the featured image, set it as the post thumbnail
                if ($input_key === '12') {
                    set_post_thumbnail($post_id, $attachment_id);
                } else {
                    // Add the attachment ID to the uploaded-attachment-ids array
                    $uploaded_attachment_ids[] = $attachment_id;
                }
        
                // Update the caption of the image
                wp_update_post(array(
                    'ID'           => $attachment_id,
                    'post_excerpt' => $_POST[$caption_key],
                    'post_parent'  => $post_id,
                ));
            // Gforms uploads this for us
            } elseif (rgar($entry, $input_key)) {
                // Handle the image upload from rgar
                $image_urls = json_decode(rgar($entry, $input_key), true);
                if (is_array($image_urls) && !empty($image_urls[0])) {
                    $attachment_id = attachment_url_to_postid($image_urls[0]);
                    
                    // If it's the featured image, set it as the post thumbnail
                    if ($input_key === '12') {
                        set_post_thumbnail($post_id, $attachment_id);
                    } else {
                        // Add the attachment ID to the uploaded-attachment-ids array
                        $uploaded_attachment_ids[] = $attachment_id;
                    }
        
                    // Update the caption of the image
                    wp_update_post(array(
                        'ID'           => $attachment_id,
                        'post_excerpt' => $_POST[$caption_key],
                        'post_parent'  => $post_id,
                    ));
                }
            }
        }

        // Update the attatchment images for the post
        update_field('field_65ba89acc66e1', $uploaded_attachment_ids, $post_id);

        // Update post tags and categories
        wp_set_post_tags($post_id, $tags);
        wp_set_post_categories($post_id, array($category_id));

        update_field('related_staff', json_decode($related_staff), $post_id);
        update_field('related_projects', json_decode($related_projects), $post_id); 
                 
        // Update related projects with the value of $post_id
        $related_projects_array = json_decode($related_projects);

        if (is_array($related_projects_array)) {
            foreach ($related_projects_array as $related_project_id) {
                update_field('related_articles', $post_id, $related_project_id);
            }
        }

    }

    public function handle_uploaded_image($file_tmp, $file_name, $caption) {
        // Assuming you have a directory to store the uploaded files
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'];
    
        // Move the uploaded file to the destination directory
        $file_path = $upload_path . '/' . $file_name;
    
        if (move_uploaded_file($file_tmp, $file_path)) {
            // File uploaded successfully
            
            // ...
    
            // Insert the image into the media library and return the attachment ID
            $attachment_id = wp_insert_attachment(array(
                'post_title'     => $file_name,
                'post_mime_type' => mime_content_type($file_path),
                'post_status'    => 'inherit',
                'post_excerpt'   => $caption,
            ), $file_path);
    
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attach_data);
    
            return $attachment_id;
        } else {
            // File upload failed
            return false;
        }
    }
    
    public function create_article($entry, $form) {
        $title = rgar( $entry, '37' ); 
        $post_id = get_page_by_title( $title, OBJECT, 'post' );

        $image_captions = array();
        $additional_images = array();

        for ($i = 0; isset($_POST['captionInput' . $i]); $i++) {
            $image_captions[] = sanitize_text_field($_POST['captionInput' . $i]);
        } 

        // FIND POST ID TO-DO
        $image_caption_data = array(
            isset($entry["gpml_ids_24"][0]) ? $entry["gpml_ids_24"][0] : false => $image_captions[0],
            isset($entry["gpml_ids_26"][0]) ? $entry["gpml_ids_26"][0] : false => $image_captions[1],
            isset($entry["gpml_ids_27"][0]) ? $entry["gpml_ids_27"][0] : false => $image_captions[2],
            isset($entry["gpml_ids_28"][0]) ? $entry["gpml_ids_28"][0] : false => $image_captions[3],
            isset($entry["gpml_ids_29"][0]) ? $entry["gpml_ids_29"][0] : false => $image_captions[4],
            isset($entry["gpml_ids_30"][0]) ? $entry["gpml_ids_30"][0] : false => $image_captions[5], 
            isset($entry["gpml_ids_31"][0]) ? $entry["gpml_ids_31"][0] : false => $image_captions[6],
        );
        $related_staff = rgar($entry, '23');
        $related_projects = rgar($entry, '32');  

        foreach($image_caption_data as $id => $caption) {
            if(!$id){
                continue;
            }
            if ( key($image_caption_data) !== $id ) {
                $additional_images[] = $id;
            } else {
                // Featured Image
                set_post_thumbnail($post_id, $id);
            }
            wp_update_post(array(
                'ID'            => $id,
                'post_excerpt'  => $caption,
            ));
        }

        update_field('additional_images', $additional_images, $post_id);

        if (!empty($related_staff)) {
            $unserialized_related_staff = json_decode($related_staff);
            if (is_array($unserialized_related_staff)) {
                update_field('field_65021ce5287c6', $unserialized_related_staff, $post_id);
            }
        }

        if (!empty($related_projects)) {
            $unserialized_related_projects = json_decode($related_projects);
            if (is_array($unserialized_related_projects)) {
                update_field('field_65a6fa6acef28', $unserialized_related_projects, $post_id);
            }
        }

    }

    public function create_project($entry, $form) {
        $title = rgar($entry, '1');
        $funding_instrument = rgar($entry, '12');
        $passthrough_entities = rgar($entry, '13');
        $non_psi_personel = rgar($entry, '14');
        $principal_investigator = rgar($entry, '15');
        $collaborators = rgar($entry, '16');
        $co_investigators = rgar($entry, '17');
        $nickname = rgar($entry, '4');
        $featured_image = rgar($entry, '5');
        $description = rgar($entry, '3');
        $project_number = rgar($entry, '6');
        $agency_award_number = rgar($entry, '7');
        $project_website = rgar($entry, '10');
        $start_date = rgar($entry, '8');
        $end_date = rgar($entry, '9');
        $related_articles = rgar($entry, '22');
        $funding_source = rgar($entry, '18');
        $funding_program = rgar($entry, '19');

        $meta_fields = [
            'field_65652c908b353' => $funding_instrument,
            'field_656541da7d94d' => $passthrough_entities,
            'field_656541b47d94c' => $non_psi_personel,
            'field_6565323600251' => $principal_investigator,
            'field_656541567d94b' => json_decode($collaborators),
            'field_656539a3fe4ba' => json_decode($co_investigators),
            'field_65652d6d24359' => $nickname,
            'field_65652c058b351' => $project_number,
            'field_65652c7e8b352' => $agency_award_number,
            'field_65652f772435e' => $project_website,
            'field_65652f0a2435c' => $start_date,
            'field_65652f552435d' => $end_date,
            'field_6574c321bde33' => json_decode($related_articles),
        ];

        $taxonomies = array(
            'funding-agency'   => $funding_source,
            'funding-program'  => $funding_program,
        );

        // Decode the JSON string for featured image
        $featured_image_array = json_decode($featured_image, true);
        // Extract the URL from the array
        $featured_image_url = isset($featured_image_array[0]) ? $featured_image_array[0] : '';

        // Create post data
        $post_data = array(
            'post_title'      => $title,
            'post_content'    => $description,
            'post_type'       => 'project',
            'post_status'     => 'publish',
            'post_author'     => get_current_user_id(),
        );

        // Insert the post and get the post ID
        $post_id = wp_insert_post($post_data);

        if(!$post_id) {
            return;
        }

        if ($featured_image_url) {
            $attachment_id = attachment_url_to_postid($featured_image_url);
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        // Update meta fields
        foreach($meta_fields as $field_key => $field_value) { 
            if (!update_field($field_key, $field_value, $post_id)) {
                // Handle error (e.g., log, notify, etc.)
            }
        }

        // Update taxonomies
        foreach ($taxonomies as $taxonomy => $term_name) {
            if(!$term_name) {
                continue;
            }
           
            $term = get_term_by('name', $term_name, $taxonomy);
            $term_id = $term ? $term->term_id : 0;

            if (!$term_id) {
                // Term doesn't exist, let's create it
                $term_info = wp_insert_term($term_name, $taxonomy);

                if (!is_wp_error($term_info)) {
                    // Term created successfully, get the term ID
                    $term_id = $term_info['term_id'];

                } else {
                    // Handle error (e.g., log, notify, etc.)
                    continue; // Skip to the next iteration
                }
            }

            // Set the post terms
            wp_set_post_terms($post_id, [$term_id], $taxonomy, false);
        }

        // Get the first term for 'funding-agency'
        $funding_agency_terms = wp_get_post_terms($post_id, 'funding-agency', array('fields' => 'ids'));
        $funding_agency_term = !empty($funding_agency_terms) ? $funding_agency_terms[0] : null;

        // Get the first term for 'funding-program'
        $funding_program_terms = wp_get_post_terms($post_id, 'funding-program', array('fields' => 'ids'));
        $funding_program_term = !empty($funding_program_terms) ? $funding_program_terms[0] : null;

        // Get existing related programs
        $existing_programs = get_field('related_programs', 'funding-agency_' . $funding_agency_term);

        // Ensure $existing_programs is an array
        $existing_programs = is_array($existing_programs) ? $existing_programs : array();

        // Add the new program to the array if it doesn't already exist
        if (!in_array($funding_program_term, $existing_programs)) {
            $existing_programs[] = $funding_program_term;

            update_field('related_programs', $existing_programs, 'funding-agency_' . $funding_agency_term);
        }

        // Similarly, for related agencies
        $existing_agencies = get_field('related_agencies', 'funding-program_' . $funding_program_term);

        // Ensure $existing_agencies is an array
        $existing_agencies = is_array($existing_agencies) ? $existing_agencies : array();

        if (!in_array($funding_agency_term, $existing_agencies)) {
            $existing_agencies[] = $funding_agency_term;

            update_field('related_agencies', $existing_agencies, 'funding-program_' . $funding_program_term);
        }
    }

    public function update_project($entry, $form) {
        // Default 
        $post_id = rgar($entry, '22');
        $content = rgar($entry, '3');
        $title = rgar($entry, '2'); 
        $featured_image = json_decode(rgar($entry, '21'));

        // Custom Taxonomies
        $funding_source = rgar($entry, '18');
        $funding_program = rgar($entry, '19');

        // Meta 
        $nickname = rgar($entry, '4');
        $project_number = rgar($entry, '6');
        $agency_award_number = rgar($entry, '7');
        $start_date = rgar($entry, '8');
        $end_date = rgar($entry, '10');
        $project_website = rgar($entry, '11');
        $non_psi_personel = rgar($entry, '12');
        $passthrough_entities = rgar($entry, '13');
        $funding_instrument = rgar($entry, '14');
        $pi = rgar($entry, '15');
        $collabs = json_decode(rgar($entry, '16'), true);
        $co_investigators = json_decode(rgar($entry, '17'));

        $post_data = array(
            'ID'           => $post_id,
            'post_content' => $content,
            'post_title'   => $title,
        );

        $meta_fields = [
            'field_65652d6d24359' => $nickname,
            'field_65652c058b351' => $project_number,
            'field_65652c7e8b352' => $agency_award_number,
            'field_65652f0a2435c' => $start_date,
            'field_65652f552435d' => $end_date,
            'field_65652f772435e' => $project_website,
            'field_656541b47d94c' => $non_psi_personel,
            'field_656541da7d94d' => $passthrough_entities,
            'field_65652c908b353' => $funding_instrument,
            'field_6565323600251' => $pi,
            'field_656541567d94b' => $collabs,
            'field_656539a3fe4ba' => $co_investigators,
        ];

        $taxonomies = array(
            'funding-agency'   => $funding_source,
            'funding-program'  => $funding_program,
        );

        // Handle Featured Image
        if (empty($_POST['featured_image']) && empty($featured_image)) {
            if (isset($_FILES['input_21']) && !empty($_FILES['input_21']['name'])) {
                $attachment_id =$this->handle_uploaded_image($_FILES['input_21']['tmp_name'], $_FILES['input_21']['name'], '');
                if($attachment_id) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            } else {
                delete_post_thumbnail($post_id);
            }
        }

        if(is_array($featured_image) && isset($featured_image[0])){
            $attachment_id = attachment_url_to_postid($featured_image[0]);
            
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
            }
        }

        // Update meta fields
        foreach($meta_fields as $field_key => $field_value) {
            
                if (!update_field($field_key, $field_value, $post_id)) {
                    // Handle error (e.g., log, notify, etc.)
                }
            
        }

        // Update taxonomies
        foreach ($taxonomies as $taxonomy => $term_name) {
            if ($term_name) {
                $term = get_term_by('name', $term_name, $taxonomy);
                $term_id = $term ? $term->term_id : 0;

                if (!$term_id) {
                    // Term doesn't exist, let's create it
                    $term_info = wp_insert_term($term_name, $taxonomy);

                    if (!is_wp_error($term_info)) {
                        // Term created successfully, get the term ID
                        $term_id = $term_info['term_id'];

                    } else {
                        // Handle error (e.g., log, notify, etc.)
                        continue;
                    }
                }

                // Set the post terms
                wp_set_post_terms($post_id, [$term_id], $taxonomy, false);
                
            }
        }

        // Get the first term for 'funding-agency'
        $funding_agency_terms = wp_get_post_terms($post_id, 'funding-agency', array('fields' => 'ids'));
        $funding_agency_term = !empty($funding_agency_terms) ? $funding_agency_terms[0] : null;

        // Get the first term for 'funding-program'
        $funding_program_terms = wp_get_post_terms($post_id, 'funding-program', array('fields' => 'ids'));
        $funding_program_term = !empty($funding_program_terms) ? $funding_program_terms[0] : null;

        // Funding Agency/Source related programs.

        // Get existing related programs
        $existing_programs = get_field('related_programs', 'funding-agency_' . $funding_agency_term);

        // Ensure $existing_programs is an array
        $existing_programs = is_array($existing_programs) ? $existing_programs : array();

        // Add the new program to the array if it doesn't already exist
        if (!in_array($funding_program_term, $existing_programs)) {
            $existing_programs[] = $funding_program_term;

            // Update the field with the modified array
            update_field('related_programs', $existing_programs, 'funding-agency_' . $funding_agency_term);
        }

        // Similarly, for related agencies
        $existing_agencies = get_field('related_agencies', 'funding-program_' . $funding_program_term);

        // Ensure $existing_agencies is an array
        $existing_agencies = is_array($existing_agencies) ? $existing_agencies : array();

        if (!in_array($funding_agency_term, $existing_agencies)) {
            $existing_agencies[] = $funding_agency_term;

            update_field('related_agencies', $existing_agencies, 'funding-program_' . $funding_program_term);
        }

        // Update the post with the new content
        wp_update_post($post_data);
    }

    /**
     * Create staff page after user registration via Gravity Forms.
     *
     * @param array $entry The form entry data.
     * @param array $form The form data.
     */
    public function create_staff_page($entry, $form) {
         
        $user_email = rgar($entry, '6'); 
        // Get the user ID based on the user email
        $user = get_user_by('email', $user_email);

        $first_name = rgar($entry, '4.3');
        $last_name = rgar($entry, '4.6');
        $position = rgar($entry, '10');
        $state = rgar($entry, '7.4');
        $country = rgar($entry, '7.6');
    
        $primary_picture = json_decode(rgar($entry, '30'));
      
        $primary_picture_array = is_array($primary_picture) ? $primary_picture : [''];
        $primary_picture_attatchment_id = isset($primary_picture_array[0]) ? attachment_url_to_postid($primary_picture_array[0]) : 0;

        $user_slug = sanitize_title($first_name . '-' . $last_name);
        $address = $state . ' '. $country;
        
        if ($user) {
            // TO-DO: Add ALL user meta
            $user_meta = array(
                'field_652f53162879f' => $position,
                'field_652f5316338be' => $address,
                'field_656d4ea2c5454' => $user_slug,
            );

            // Field group ID for the primary pictures
            $profile_pictures_group_subfields = array(
                'field_65821a416331d' => $primary_picture_attatchment_id,
                'field_65821a686331e' => 0,
                'field_65821aa06331f' => 0,
                'field_65821aaf63320' => 0      
            );

            // profile pictures
            update_field('field_658219e86331c', $profile_pictures_group_subfields, 'user_' . $user->data->ID);

            foreach ($user_meta as $field => $value) {
                update_field($field, $value, 'user_' . $user->data->ID);
            }    

            
        }
    }

    /**
     * Update staff page after form submission via Gravity Forms. These fields are not available to be updated by a staff member
     *
     * @param array $entry The form entry data.
     * @param array $form The form data.
     */
    public function update_staff_page($entry, $form) {
        $user_id = rgar($entry, '8');

        if(!$user_id) {
            error_log('No user ID found');
        }

        $email = rgar($entry, '3');
        $name = rgar($entry, '9');
        $position = rgar($entry, '4');
        $address = rgar($entry, '7');

        $name_parts = explode(' ', $name, 3);

        // Assign variables based on the count of name parts
        $prefix = (count($name_parts) > 2) ? $name_parts[0] : '';
        $first_name = (count($name_parts) > 2) ? $name_parts[1] : $name_parts[0];
        $last_name = (count($name_parts) > 2) ? $name_parts[2] :  $name_parts[1];

        // Display name without prefix
        $display_name = $first_name . ' ' . $last_name;

        wp_update_user(array(
            'ID' => $user_id,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name,
            // Add other fields you want to update
        ));

        update_user_meta($user_id, 'nickname', $name);
        update_field('position', $position, 'user_' . $user_id);
        update_field('location', $address, 'user_' . $user_id);
    }
    
    public function add_custom_roles() {
        remove_role('staff_member');
        // Add Staff Member Role
        add_role(
            'staff_member',
            __('Staff Member'),
            array(
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'upload_files' => true,
                'read_private_posts' => true,
                'edit_private_posts' => true,
                'delete_private_posts' => true,
                'publish_private_posts' => true,
                'read_published_posts' => true,
                'edit_published_posts' => true,
                'delete_published_posts' => true,
                // Add other capabilities as needed
            )
        );

        // Optionally, define a new role 'staff_member_editor' inheriting from 'staff_member'
        add_role(
            'staff_member_editor',
            __('Staff Member Editor'),
            get_role('staff_member')->capabilities
        );
    }  

    public function setup_acf_filters() {
        if (!current_user_can('administrator')) {
            add_filter('acf/prepare_field/name=related_posts', array($this, 'my_acf_prepare_field'));
            add_filter('acf/prepare_field/name=related_projects_and_initiatives', array($this, 'my_acf_prepare_field'));
            add_filter('acf/prepare_field/name=status', array($this, 'my_acf_prepare_field'));
            add_filter('acf/prepare_field/name=position', array($this, 'my_acf_prepare_field'));
        }
    }

    public function my_acf_prepare_field( $field ) {
    
        if( $field["label"] == 'Related Posts') {
            return false;
        } elseif( $field["label"] == 'Related Projects and Initiatives') {
            return false;
        } elseif( $field["label"] == 'Status') {
            return false;
        } elseif( $field["label"] == 'Position') {
            return false;
        }
        return $field;
    }

    public function show_current_user_attachments($query) {
        $user_id = get_current_user_id();
    
        // Check if the user is an administrator
        if (current_user_can('activate_plugins')) {
            return $query; // Admins can see all media, no modification needed
        }
    
        // Check if the user is logged in
        if ($user_id) {
            // Check if the user has the 'staff_member' or 'staff_member_editor' role
            $user = get_user_by('ID', $user_id);
            $allowed_roles = ['staff_member', 'staff_member_editor'];
    
            if (array_intersect($allowed_roles, (array)$user->roles)) {
                // Allow staff members and staff member editors to edit their own media attachments
                $query['author'] = $user_id;
                $query['can_edit_attachments'] = true;
            }
        }
    
        return $query;
    }
    
    public function custom_rewrite_rules() {
        // Rewrite rule for user-profile/user_nicename
        add_rewrite_rule('^staff/profile/([^/]+)/?$', 'index.php?pagename=user-profile&user_nicename=$matches[1]', 'top');
    
        // Rewrite rules for other sections
        $sections = array(
            'professional-history',
            'honors-and-awards'
        );
    
        foreach ($sections as $section) {
            add_rewrite_rule("staff/profile/{$section}/([^/]+)/?$", 'index.php?pagename=' . $section . '&user_nicename=$matches[1]', 'top');
        }
    }

    public function custom_query_vars($query_vars) {
        $query_vars[] = 'user_nicename';
        return $query_vars;
    }

    function modify_menu_items($items, $args) {
        // Check if it's the primary or off-canvas menu and the user is logged in
        if ((($args->theme_location == 'primary' || $args->theme_location == 'off-canvas') && !is_admin()) || ($args->menu->slug == 'primary' && !is_admin())) {
            // Check if "Staff" menu item exists
            $staff_menu_item_position = strpos($items, 'menu-item-22859');
            
            if ($staff_menu_item_position !== false) {
                // Check if the user is logged in
                if (is_user_logged_in()) {
                    // User is logged in, add Logout link
                    $current_user = wp_get_current_user();
                    $logout_url = wp_logout_url(home_url('/')); // Logout URL with redirect to home
                    $logout_link = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-logout"><a href="' . esc_url($logout_url) . '">Logout</a></li>';

                     // Get the user's slug from the usermeta field
                    $user_slug = get_user_meta($current_user->ID, 'user_slug', true);
                    $profile_url = home_url('/staff/profile/' . $user_slug); // Adjust the URL structure as needed
                    $profile_link = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-profile"><a href="' . esc_url($profile_url) . '">My Profile</a></li>';

                    // Find the end of "Staff" submenu and insert Logout and My Profile links
                    $submenu_end_position = strpos($items, '</ul>', $staff_menu_item_position);
                    if ($submenu_end_position !== false) {
                        $items = substr_replace($items, $profile_link . $logout_link, $submenu_end_position, 0);
                    }
                } else {
                    // User is logged out, add Login link
                    $login_url = wp_login_url(home_url('/')); // Login URL with redirect to home
                    $login_link = '<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-login"><a href="' . esc_url($login_url) . '">Login</a></li>';
                    // Find the end of "Staff" submenu and insert Login link
                    $submenu_end_position = strpos($items, '</ul>', $staff_menu_item_position);
                    if ($submenu_end_position !== false) {
                        $items = substr_replace($items, $login_link, $submenu_end_position, 0);
                    }
                }
            }
        }
        return $items;
    }

    public function enqueue_acf_select2_and_dequeue_wcd_select2_admin() {
        // Dequeue the West Coast Digital version of Select2 in the admin area.
        wp_dequeue_style('select2');
        wp_deregister_style('select2');
        wp_dequeue_script('select2');
        wp_deregister_script('select2');
    
        // Load the proper compatible version for ACF and Social Share
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
        // Must be full version
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js', array('jquery') );
    }

    public function enqueue_custom_scripts() {
        // JS for the user profile pages (only load if on the /profile/ route)
        if (is_page() && strpos($_SERVER['REQUEST_URI'], '/profile/') !== false) {
            wp_enqueue_script('user-profile-scripts', get_stylesheet_directory_uri() . '/js/script.js', array('jquery'), '', true);
        }
            
        if(!is_singular('post')){
            wp_enqueue_script('slick-init', get_stylesheet_directory_uri() . '/js/slick-init.js', array('jquery'), '1.0', true);
        }

        if(is_singular('project')){
            wp_enqueue_script('single-project-scripts', get_stylesheet_directory_uri() . '/js/projects/project.js', array('jquery'), '1.0', true);
        }

        if(is_page('edit-staff-page')){
            wp_enqueue_script('edit-staffpage-scripts', get_stylesheet_directory_uri() . '/js/editStaffPage.js', array('jquery'), '1.0', true);
        }

        if(is_page('edit-article')){
            wp_enqueue_script('edit-article', get_stylesheet_directory_uri() . '/js/editArticle.js', array('jquery'), '1.0', true);
        }
            
        if(is_page('press-submission')){
            wp_enqueue_script('article-form', get_stylesheet_directory_uri() . '/js/articleForm.js', array('jquery'), '1.0', true);
        }

        if(is_page('edit-project')){
            wp_enqueue_script('edit-projects-scripts', get_stylesheet_directory_uri() . '/js/projects/editProject.js', array('jquery'), '1.0', true);
        }
    }
    
    public function enqueue_slick_from_cdn() {
        // Register and enqueue Slick.js from the CDN
        wp_enqueue_script('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);

        // Enqueue Slick.js CSS from the CDN
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', array(), '1.8.1', 'all');

        // Enqueue Slick.js theme CSS from the CDN (optional)
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', array(), '1.8.1', 'all');
    }

    public function related_users_shortcode($atts) {
        // Extract shortcode attributes, including 'ID' and 'class' if provided
        $atts = shortcode_atts(array(
            'ID' => get_the_ID(), // Default to the current post's ID if not provided
            'class' => 'normal', // Default class is 'normal'
            'widget' => false,
            'page' => '',
        ), $atts);
    
        // Get the post ID from the 'ID' attribute
        $post_id = intval($atts['ID']);
       
        // Get the related users from the ACF relationship field
        $related_users = get_field('related_staff', $post_id);
    
        // Determine the appropriate CSS class based on the 'class' attribute
        $carousel_class = 'related-staff-carousel';
        if ($atts['class'] === 'large') {
            $carousel_class = 'related-staff-carousel-large';
        } elseif($atts['class'] === 'medium') {
            $carousel_class = 'related-staff-carousel-medium';
        }
        
        // Output the user information
        if (!empty($related_users)) {
            
            $output_class = $carousel_class;

            if (isset($atts['page'])) {
                $page_number = intval($atts['page']);
                $output_class .= ' page' . $page_number;
            }

            if ($atts['widget']) {
                $output = '<h2 class="related-staff-heading">RELATED STAFF</h2>';
                $output .= '<ul class="' . esc_attr($output_class) . '">';
            } else {
                $output = '<ul class="' . esc_attr($output_class) . '">';
            }
            
            foreach ($related_users as $user) {
                $user_id = $user->ID;
                $user_slug = get_field('user_slug', 'user_' . $user_id);
                $user_permalink = home_url() . '/staff/profile/' . $user_slug;
                
                
                $profile_images = get_field('profile_pictures', 'user_' .$user_id);

                if($profile_images){
                    $profile_img = $profile_images['icon_picture'] ? $profile_images['icon_picture'] : null;
                    if($profile_img){
                        $profile_img_url = $profile_img['url'];
                        $profile_img_alt = $profile_img['alt'];
                    } else {
                        $profile_img = $profile_images["primary_picture"] ? $profile_images["primary_picture"] : null;
                        if($profile_img){
                            $profile_img_url = $profile_img['url'];
                            $profile_img_alt = $profile_img['alt'];
                        }
                    }     
                } 

                if(!$profile_img) {
                    $default_image = get_field('default_user_picture', 'option');
                    $profile_img_url = $default_image['url'];
                    $profile_img_alt = $default_image['alt'];
                }
    
                $output .= '<li class="related-user">';
                $output .= '<img class="related-staff__image" src="' . $profile_img_url . '" alt="' . esc_attr($profile_img_alt) . '">';
                $output .= '<a class="related-staff__name" href="' . $user_permalink . '">' . esc_html($user->display_name) . '</a>';
                $output .= '</li>';
            }
            $output .= '</ul>';
            return $output;
        } 
        
    }

    function load_more_posts_shortcode($atts) {
        // Shortcode attributes
        $atts = shortcode_atts(
            array(
                'post_type'      => 'post',
                'posts_per_page' => 6,
                'category'       => '',
            ),
            $atts,
            'load_more_posts'
        );
    
        // Enqueue your script that handles the AJAX request
        wp_enqueue_script('load-more-posts', get_stylesheet_directory_uri() . '/js/loadMorePosts.js', array('jquery'), null, true);
    
        // Localize the script with shortcode attributes
        wp_localize_script('load-more-posts', 'load_more_params', array(
            'post_type'      => $atts['post_type'],
            'posts_per_page' => intval($atts['posts_per_page']),
            'category'       => $atts['category'],
        ));
    
        // Shortcode output
        ob_start();
        ?>
        <div id="load-more-posts-container">
            <!-- Posts will be appended here -->
        </div>
        <div class="loader-container">
                        
        </div>
        <button id="load-more-posts-button">Load More<i class="fa-solid fa-angle-right"></i></button>
        <?php
        return ob_get_clean();
    }
    
    public function update_user_fields($post_id) {
        if (is_page('edit-user')) {

            $user_id = str_replace('user_', '', $post_id);
            
            $field_groups = array(
                'field_658219e86331c', // profile pictures group field key
                'field_652f531642964', // professional interests group field key
                'field_65318b433fabd', // professional history group field key
                'field_65318bc05ad17' // honors and awards group field key
            );

            $profile_pictures_group_field = 'field_658219e86331c';
            $profile_pictures_group_subfields = array(
                'primary_picture' => ($_POST['acf']['field_65821a416331d']),
                'professional_history_picture' => ($_POST['acf']['field_65821a686331e']),
                'honors_and_awards_picture' => ($_POST['acf']['field_65821aa06331f']),
                'icon_picture' => ($_POST['acf']['field_65821aaf63320']),
            );

            $professional_interest_group_field = 'field_652f531642964';
            $professional_interest_group_subfields = array(
                'professional_interests_text' => ($_POST['acf']['field_6531887586121']),
                'professional_interests_images' => ($_POST['acf']['field_6553c9ab418c0']),
                'professional_interests_image_caption' => ($_POST['acf']['field_653188ad86124']),
            );

            $professional_history_group_field = 'field_65318b433fabd';
            $professional_history_group_subfields = array(
                'professional_history_text' => ($_POST['acf']['field_65318b433fabe']),
                'professional_history_images' => ($_POST['acf']['field_6553c9eac423f']),
                'professional_history_image_caption' => ($_POST['acf']['field_65318b433fac1']),
            );

            $honors_and_awards_group_field = 'field_65318bc05ad17';
            $honors_and_awards_group_subfields = array(
                'honors_and_awards_text' => ($_POST['acf']['field_65318bc05ad18']),
                'honors_and_awards_images' => ($_POST['acf']['field_6553ca6855149']),
                'honors_and_awards_image_caption' => ($_POST['acf']['field_65318bc05ad1b']),
            );
    
            $meta_fields = array(
                'location' => sanitize_text_field($_POST['acf']['field_652f5316338be']),
                'cv' => sanitize_text_field($_POST['acf']['field_652f53163ade2']),
                'publications_link' => sanitize_text_field($_POST['acf']['field_6579fb74c2164']),
                'publications_url' => sanitize_text_field($_POST['acf']['field_65c29cc948514']),
                'personal_page' => sanitize_text_field($_POST['acf']['field_6531839aff808']),
                'display_in_directory' => sanitize_text_field($_POST['acf']['field_653fddd65f0b3']),
                'targets_of_interests' => sanitize_text_field($_POST['acf']['field_6594dae77bc2e']),
                'disciplines_techniques' => sanitize_text_field($_POST['acf']['field_6594dafa7bc2f']),
                'missions' => sanitize_text_field($_POST['acf']['field_6594db127bc30']),
                'mission_roles' => sanitize_text_field($_POST['acf']['field_6594db247bc31']),
                'instruments' => sanitize_text_field($_POST['acf']['field_6594db2c7bc32']),
                'facilities' => sanitize_text_field($_POST['acf']['field_6594db387bc33']),
                'twitter_x' => sanitize_text_field($_POST['acf']['field_65ca58de9e649']),
                'linkedin' => sanitize_text_field($_POST['acf']['field_65ca58f69e64a']),
                'youtube' => sanitize_text_field($_POST['acf']['field_65ca59179e64c']),
                'facebook' => sanitize_text_field($_POST['acf']['field_65ca59099e64b']),
                'instagram' => sanitize_text_field($_POST['acf']['field_65ca59209e64d']),
                'github' => sanitize_text_field($_POST['acf']['field_65d905aa86ece']),
                'orchid' => sanitize_text_field($_POST['acf']['field_65d932851c5fa']),
                'gscholar' => sanitize_text_field($_POST['acf']['field_65d932951c5fb']),
            );
    
            foreach ($meta_fields as $meta_key => $meta_value) {
                update_field($meta_key, $meta_value, 'user_'.$user_id);
            }

            update_field($profile_pictures_group_field, $profile_pictures_group_subfields, 'user_'.$user_id);
            update_field($professional_interest_group_field, $professional_interest_group_subfields, 'user_'.$user_id);
            update_field($professional_history_group_field, $professional_history_group_subfields, 'user_'.$user_id);
            update_field($honors_and_awards_group_field, $honors_and_awards_group_subfields, 'user_'.$user_id);
        }
    }

    public function edit_article_link_shortcode() {
        // Check if the user is logged in
        if (is_user_logged_in()) {
            // Check if the user has editor or higher capabilities or the edit_staff_member role
            if (current_user_can('edit_others_posts') || in_array('edit_staff_member', (array) $current_user->roles)) {
                $base_url = get_bloginfo('url');
                $edit_project_url = trailingslashit($base_url) . 'edit-article?article-name=' . get_the_ID();
    
                // Return the link
                return '<a href="' . esc_url($edit_project_url) . '">Edit Press Submission</a>';
            }
        }
    
        // If conditions are not met, return an empty string or other content as needed
        return '';
    }
    
    /**
     * Generates initial related posts markup based on a specified ACF relationship field.
     *
     * @param int $posts_per_page Number of posts to retrieve per page (default: 6).
     * @param string $related_field The custom field name containing related posts (default: 'related_posts').
     * @param int|null $relation_id The ID of the relationship to fetch posts for.
     *
     * @return bool Returns false if there's an error or there are no more posts beyond the posts per page set, otherwise returns true
     */
    public function generate_initial_related_posts($posts_per_page = 6, $related_field = 'related_posts', $relation_id = null) {
        if(!$relation_id){
            // Return some error which says no relationship can be found without ID
            return false;
        } 

        $related_posts = get_field($related_field, $relation_id);

        if(!$related_posts){
            // Return some error which says no posts could be found
            return false;
        }

        $has_past_projects = false;
        $has_active_projects = false;

        // Filter out past projects and set the flag if a past project is found
        $active_related_posts = array_filter($related_posts, function($post) use (&$has_past_projects, &$has_active_projects){
            if ($post->post_type !== 'project') {
                return false;
            }
            $is_past_project = PSI_Child_Theme::is_past_project($post);

          
            if ($is_past_project) {
                $has_past_projects = true;
            } else {
                $has_active_projects = true;
            }
            return !$is_past_project;
        });

        // Sort the related posts by date in descending order
        usort($related_posts, function ($a, $b) {
            $date_a = strtotime($a->post_date);
            $date_b = strtotime($b->post_date);

            return ($date_a < $date_b) ? 1 : -1;
        });

        if($active_related_posts){
            
            $initial_posts = array_slice($active_related_posts, 0, $posts_per_page);
        } else {
            // Slice the array to get the initial posts
            $initial_posts = array_slice($related_posts, 0, $posts_per_page);
        } 

        if(empty($initial_posts)) {
            // Return some error which says that there were no initial posts found  
            return false;
        }

        ob_start();


        if($has_active_projects){

            foreach($initial_posts as $post) {
                get_template_part('template-parts/projects/activity-banner', '', array(
                    'post' => $post,
                ));
            }
            // Check if there are additional posts beyond the initial 6
            $has_more_posts = count($active_related_posts) > $posts_per_page;
            
            
        }
        
        if(!$has_active_projects){
            $markup = 'no posts';
            $has_more_posts = false;
        } 

        if($related_field == 'related_posts' || $related_field == 'related_articles') {
            foreach ($initial_posts as $post) {
                get_template_part('template-parts/related-post', '', array(
                    'post' => $post,
                ));
            }
            // Check if there are additional posts beyond the initial 6
            $has_more_posts = count($related_posts) > $posts_per_page;
        }

        
        $markup = ob_get_clean(); 
       
        return array(
            'markup' => $markup,
            'has_more_posts' => $has_more_posts,
            'has_past_projects' => $has_past_projects,
        );
    }

    /**
    * Check if a given post is a past project based on its end date.
    *
    * @param object $post The WordPress post object.
    * @return bool True if the post is a past project, false otherwise.
    */
    public static function is_past_project($post) {
        // Check if $post is a valid object
        if (!is_object($post) || empty($post->ID)) {
            return false; // Return false if $post is not valid
        }
    
        if ($post->post_type !== 'project') {
            return false;
        }
        
        // Get the end_date
        $end_date = get_field('end_date', $post->ID);
        
        // Check if end_date exists
        if (!$end_date) {
            return false; // Return false if end_date does not exist
        }
        
        // Convert end_date to DateTime object
        $end_date = DateTime::createFromFormat('m/d/Y', $end_date);
    
        // Get the current date
        $current_date = new DateTime();
    
        // Check if end_date is past the current date
        if ($end_date && $end_date < $current_date) {
            
            return true; // Project is past
        } else {
            return false; // Project is ongoing or in the future
        }
    }
    
}

// Instantiate the main theme class
$psi_theme = new PSI_Child_Theme();