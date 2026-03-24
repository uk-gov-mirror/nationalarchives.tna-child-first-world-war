<?php

function tnatheme_globals() {
    global $pre_path;
    global $pre_crumbs;
    $headers = apache_request_headers();
    if ( isset($_SERVER['HTTP_X_NGINX_PROXY']) && isset($headers['X-Host-Type']) && $headers['X-Host-Type'] == 'public' ) {
        $pre_crumbs = array(
            'First World War' => '/first-world-war/'
        );
        $pre_path = '/first-world-war';
    } elseif (substr($_SERVER['REMOTE_ADDR'], 0, 3) === '10.') {
        $pre_path = '';
        $pre_crumbs = array(
            'First World War' => '/'
        );
    } else {
        $pre_crumbs = array(
            'First World War' => '/first-world-war/'
        );
        $pre_path = '/first-world-war';
    }
}
if ( $_SERVER['SERVER_ADDR'] !== $_SERVER['REMOTE_ADDR'] ) {
    tnatheme_globals(); } else {
    $pre_path = '';
    $pre_crumbs = array(
        'First World War' => '/'
    );
}

function dequeue_parent_style() {
    wp_dequeue_style('tna-styles');
    wp_deregister_style('tna-styles');
}
add_action( 'wp_enqueue_scripts', 'dequeue_parent_style', 9999 );
add_action( 'wp_head', 'dequeue_parent_style', 9999 );

// Enqueue styles
function tna_child_styles() {
    wp_register_style( 'tna-parent-styles', get_template_directory_uri() . '/css/base-sass.min.css', array(), EDD_VERSION, 'all' );
    wp_register_style( 'tna-child-styles', get_stylesheet_directory_uri() . '/style.css', array(), '0.1', 'all' );
    wp_enqueue_style( 'tna-parent-styles' );
    wp_enqueue_style( 'tna-child-styles' );
    global $wp_styles;
    wp_enqueue_style( 'tna-child-ie', get_stylesheet_directory_uri() . "/ie.css", array()  );
    $wp_styles->add_data( 'tna-child-ie', 'conditional', 'IE' );
}
add_action( 'wp_enqueue_scripts', 'tna_child_styles' );

function tna_child_scripts() {
    if ( is_front_page() ) {
        wp_register_script( 'tna-fww', get_stylesheet_directory_uri() . '/tna-fww.js', array(),
            EDD_VERSION, true );
        wp_register_script( 'equal-heights', get_template_directory_uri() . '/js/jQuery.equalHeights.js', array(),
            EDD_VERSION, true );
        wp_register_script( 'equal-heights-var', get_template_directory_uri() . '/js/equalHeights.js', array(),
            EDD_VERSION, true );
        wp_register_script( 'moment-js', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment.js' );
        wp_enqueue_script( 'tna-fww' );
        wp_enqueue_script( 'equal-heights' );
        wp_script_add_data( 'equal-heights', 'conditional', 'lte IE 9' );
        wp_enqueue_script( 'equal-heights-var' );
        wp_script_add_data( 'equal-heights-var', 'conditional', 'lte IE 9' );
        wp_enqueue_script( 'moment-js' );
    }
}
add_action( 'wp_enqueue_scripts', 'tna_child_scripts' );

function add_categories_to_pages() {
    register_taxonomy_for_object_type( 'category', 'page' );
}
add_action( 'init', 'add_categories_to_pages' );

if ( ! is_admin() ) {
    add_action( 'pre_get_posts', 'category_archives' );
}
function category_archives( $wp_query ) {
    $my_post_array = array('post','page');
    if ( $wp_query->get( 'category_name' ) || $wp_query->get( 'cat' ) )
        $wp_query->set( 'post_type', $my_post_array );
}

add_action( 'after_setup_theme', 'wpdocs_theme_setup' );
function wpdocs_theme_setup() {
    add_image_size( 'feature-thumb', 640, 320, true );
    add_image_size( 'default-page-header', 820, 546 ); // for default page feature image
}

// Dynamic blog content via RSS feed
function fww_rss( $rssUrl, $id ) {
    // Do we have this information in our transients already?
    $transient = get_transient( 'tna_rss_blog_transient' . $id );
    // Yep!  Just return it and we're done.
    if( ! empty( $transient ) ) {
        echo $transient ;
        // Nope!  We gotta make a call.
    } else {
        // Get feed source.
        $content = file_get_contents( $rssUrl );
        if ( $content !== false ) {
            $x = new SimpleXmlElement( $content );
            $n = 0;
            // Loop through each feed item and display each item
            foreach ( $x->channel->item as $item ) :
                if ( $n == 1 ) {
                    break;
                }
                $enclosure  = $item->enclosure['url'];
                $namespaces = $item->getNameSpaces( true );
                $dc         = $item->children( $namespaces['dc'] );
                $pubDate    = $item->pubDate;
                $pubDate    = date( "l d M Y", strtotime( $pubDate ) );
                $html = '<div class="col-sm-6"><div class="card clearfix">';
                if ( $enclosure ) {
                    $html .= '<a href="' . $item->link . '" title="' . $item->title . '">';
                    $html .= '<div class="entry-thumbnail" style="background: url(' . $enclosure . ') no-repeat center center;background-size: cover;">';
                    // $html .= '<img src="' . $enclosure . '" class="img-responsive" alt="' . $item->title . '">';
                    $html .= '</div></a>';
                }
                $html .= '<div class="entry-content"><small>Blog</small><h2><a href="' . $item->link . '">';
                $html .= $item->title;
                $html .= '</a></h2>';
                $html .= '<small>' . $dc->creator . ' | ' . $pubDate . '</small>';
                $html .= '<p>' . $item->description . '</p><ul class="child"><li><a href="http://blog.nationalarchives.gov.uk/blog/tag/first-world-war/">Join us on our blog</a></li></ul></div>';
                $html .= '</div></div>';
                $n ++;
            endforeach;
            set_transient( 'tna_rss_blog_transient' . $id, $html, HOUR_IN_SECONDS );
            echo $html;
        }
        else {
            echo '<div class="col-md-6"><div class="card"><div class="entry-content"><h2>First World War blog</h2><ul class="child"><li><a href="http://blog.nationalarchives.gov.uk/blog/tag/first-world-war/">Join us on our blog</a></li></ul></div></div></div>';
        }
    }
}

// Dynamic news content via RSS feed
function fww_news_rss( $rssUrlNews, $id ) {
    // Do we have this information in our transients already?
    $transient_news = get_transient( 'tna_rss_news_transient' . $id );
    // Yep!  Just return it and we're done.
    if( ! empty( $transient_news ) ) {
        echo $transient_news ;
        // Nope!  We gotta make a call.
    } else {
        // Get feed source.
        $content = file_get_contents( $rssUrlNews );
        if ( $content !== false ) {
            $x = new SimpleXmlElement( $content );
            $n = 0;
            // Loop through each feed item and display each item
            foreach ( $x->channel->item as $item ) :
                if ( $n == 1 ) {
                    break;
                }
                $enclosure  = $item->enclosure['url'];
                $pubDate    = $item->pubDate;
                $pubDate    = date( "l d M Y", strtotime( $pubDate ) );
                $img        = str_replace( home_url(), '', get_stylesheet_directory_uri() ) . '/img/thumb-news.jpg';
                $link       = str_replace( 'livelb', 'www', $item->link );
                $html = '<div class="col-sm-6"><div class="card clearfix">';
                if ( $enclosure ) {
                    $html .= '<a href="' . $link . '" title="' . $item->title . '">';
                    $html .= '<div class="entry-thumbnail" style="background: url(' . $enclosure . ') no-repeat center center;background-size: cover;">';
                    // $html .= '<img src="' . $enclosure . '" class="img-responsive" alt="' . $item->title . '">';
                    $html .= '</div></a>';
                }
                if ( !$enclosure ) {
                    $html .= '<a href="' . $link . '" title="' . $item->title . '">';
                    $html .= '<div class="entry-thumbnail" style="background: url(' . $img . ') no-repeat center center;background-size: cover;">';
                    // $html .= '<img src="' . $img . '" class="img-responsive" alt="' . $item->title . '">';
                    $html .= '</div></a>';
                }
                $html .= '<div class="entry-content"><small>News</small><h2><a href="' . $link . '">';
                $html .= $item->title;
                $html .= '</a></h2>';
                $html .= '<small>' . $pubDate . '</small>';
                preg_match( "/<p>(.*)<\/p>/", $item->description, $matches );
                $intro = strip_tags($matches[1]);
                $html .= '<p>' . first_sentence( $intro ) . '</p><ul class="child"><li><a href="http://www.nationalarchives.gov.uk/about/news/?news-tag=first-world-war&news-view=child" title="Read more news">More news</a></li></ul></div>';
                $html .= '</div></div>';
                $n ++;
            endforeach;
            set_transient( 'tna_rss_news_transient' . $id, $html, HOUR_IN_SECONDS );
            echo $html;
        }
        else {
            echo '<div class="col-md-6"><div class="card"><div class="entry-content"><h2>First World War news</h2><ul class="child"><li><a href="http://www.nationalarchives.gov.uk/about/news/?news-tag=first-world-war&news-view=child">Join us on our news page</a></li></ul></div></div></div>';
        }
    }
}

/* START OF code which manages forms on the Research guides */
if (!function_exists('render_form')) :
    function render_form($atts) {
        $file_name = $atts['form'];
        $local_path = get_stylesheet_directory() . '/';
        $file_content = file_get_contents($local_path . $atts['form'] . '.php');
        if (strlen($file_content) > 0) {
            return $file_content;
        } else {
            return 'No such file found: ' . $local_path . $file_name;
        }
    }
endif;
add_shortcode('guided-search', 'render_form');
/* END OF code which manages forms on the Research guides */

function fww_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'fww_dashboard_widget',
        'First World War portal information',
        'fww_dashboard_widget_function'
    );
}
add_action( 'wp_dashboard_setup', 'fww_add_dashboard_widgets' );

function fww_dashboard_widget_function() {
    echo "
        <p>The first five content boxes on the landing page are dynamic.</p>
        <ul>
            <li>Box 1: Content from TNA's Eventbrite account display the upcoming event with the keyword 'First World War'</li>
            <li>Box 2 & 3: Content from pages within the site categorised 'Feature'</li>
            <li>Box 4: Content from TNA's blog displaying the latest blog post tagged 'First World War'</li>
            <li>Box 5: Content from TNA's news displaying the latest news item categorised 'First World War portal news'</li>
        </ul>
        <p>The sections below the dynamic content are static content.</p>
    ";
}
