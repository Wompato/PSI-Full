<?php
/*
Template Name: Custom Projects Archive
*/

get_header();
?>

<div class="archive-navigation">
    <h2>Funding Agencies</h2>
    <ul>
        <?php
        // Get all funding agency terms
        $funding_agencies = get_terms(array(
            'taxonomy' => 'funding-agency',
            'hide_empty' => false, // Show even if they don't have projects associated
        ));

        // Display each funding agency as a navigation item
        foreach ($funding_agencies as $agency) {
            echo '<li><a href="' . esc_url(add_query_arg('agency_id', $agency->term_id, get_permalink())) . '">' . $agency->name . '</a></li>';
        }
        ?>
    </ul>
</div>

<div class="archive-programs">
    <h2>Funding Programs</h2>
    <?php
    $nasa_agency = get_term_by('slug', 'nasa', 'funding-agency');
    // Check if a funding agency is selected
    $selected_agency_id = isset($_GET['agency_id']) ? $_GET['agency_id'] : $nasa_agency->term_id;

    // Get the programs associated with the selected funding agency
    if (!empty($selected_agency_id)) {
        // Get related programs for the selected agency
        $programs = get_field('related_programs', 'funding-agency_' . $selected_agency_id);

        // Default program ID
        $default_program_id = '';

        // If there are programs, set the default program ID to the first program
        if (!empty($programs)) {
            $default_program_id = $programs[0];
        }


        // Display related programs for the selected agency
        if (!empty($programs)) {
            echo '<ul class="program-list">';
            foreach ($programs as $program_id) {
                $program = get_term($program_id, 'funding-program');
                if ($program && !is_wp_error($program)) {
                    // Add query arguments to the program links
                    $program_link = esc_url(add_query_arg(array('agency_id' => $selected_agency_id, 'program_id' => $program_id), get_permalink()));
                    echo '<li><a href="' . $program_link . '">' . $program->name . '</a></li>';
                }
            }
            echo '</ul>';
        } else {
            echo '<p>Sorry, there are no programs for this funding agency</p>';
        }
    }
    ?>
</div>


<div class="archive-active-projects">
    <h2>Active Projects</h2>
    <?php
    $program_id = isset($_GET['program_id']) ? $_GET['program_id'] : $default_program_id;

    // Query for active projects associated with the selected program
    $args = array(
        'post_type' => 'project',
        'posts_per_page' => 5, // Number of projects per page
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1, // Get current page number
        'tax_query' => array(
            array(
                'taxonomy' => 'funding-program',
                'terms' => $program_id,
                'operator' => 'IN',
            ),
        ),
        // Custom meta query to check for active projects based on end_date
        'meta_query' => array(
            array(
                'key' => 'end_date',
                'value' => date('Y-m-d'), // Today's date
                'compare' => '>=', // Compare with greater than or equal to
                'type' => 'DATE',
            ),
        ),
    );

    $projects_query = new WP_Query($args);

    // Display active projects
    if ($projects_query->have_posts()) {
        echo '<div>';
        // Display the program name
        $program = get_term($program_id, 'funding-program');
        if ($program && !is_wp_error($program)) {
            echo '<h3>' . $program->name . '</h3>';
        }
        echo '<ul>';
        while ($projects_query->have_posts()) {
            $projects_query->the_post();
            // Include custom template part to display each project
            get_template_part('template-parts/projects/activity-banner', '', array(
                'post' => $post,
            ));
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>Sorry, no Active Projects found for the selected program.</p>';
    }

    // Pagination
    echo '<div class="pagination">';
    echo paginate_links(array(
        'total' => $projects_query->max_num_pages,
        'current' => max(1, get_query_var('paged')),
    ));
    echo '</div>';

    wp_reset_postdata();
    ?>
</div>

<?php get_footer(); ?>
