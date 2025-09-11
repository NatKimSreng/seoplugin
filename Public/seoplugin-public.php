<?php
/**
 * SEOPlugin Public Functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SEOPlugin_Public {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_head', [ $this, 'add_meta_tags' ] );
        add_action( 'wp_head', [ $this, 'add_jsonld_snippet' ] );
        add_filter( 'document_title_parts', [ $this, 'override_title' ] );
        add_shortcode( 'seoplugin_keywords', [ $this, 'render_shortcode' ] );
        add_shortcode( 'site_domain_url', [ $this, 'site_domain_url_shortcode' ] );
        add_shortcode( 'seoplugin_breadcrumbs', [ $this, 'breadcrumbs_shortcode' ] );
        add_action( 'init', [ $this, 'register_sitemap' ] );
    }

    // Enqueue CSS and JS
    public function enqueue_assets() {
        wp_enqueue_style(
            'seoplugin-styles',
            SEOPLUGIN_URL . 'assets/css/seoplugin.css',
            [],
            SEOPLUGIN_VERSION
        );

        wp_enqueue_script(
            'seoplugin-scripts',
            SEOPLUGIN_URL . 'assets/js/seoplugin.js',
            [ 'jquery' ],
            SEOPLUGIN_VERSION,
            true
        );
    }

    // Add meta tags to head
    public function add_meta_tags() {
        if ( is_singular() ) {
            global $post;
            $title = get_post_meta($post->ID, '_seoplugin_meta_title', true) ?: get_the_title($post->ID);
            $description = get_post_meta($post->ID, '_seoplugin_meta_description', true) ?: get_the_title($post->ID);
            $og_image_id = get_post_meta($post->ID, '_seoplugin_og_image_id', true);
            $robots_meta = get_post_meta($post->ID, '_seoplugin_robots_meta', true);
            $canonical_url = get_post_meta($post->ID, '_seoplugin_canonical_url', true);
            $meta_keywords = get_post_meta($post->ID, '_seoplugin_meta_keywords', true);
            
            // Load custom image size og_custom
            if (is_numeric($og_image_id)) {
                $og_img = wp_get_attachment_image_src($og_image_id, 'full');
                $og_image_url = !empty($og_img) ? $og_img[0] : '';
            } else {
                $og_image_url = esc_url($og_image_id);
            }
            $meta = wp_get_attachment_metadata($og_image_id);
            $image_width = '';
            $image_height = '';

            $url = get_permalink($post->ID);
            $canonical_url = $canonical_url ?: $url;

            if (!$description) {
                $description = wp_strip_all_tags(get_the_excerpt($post->ID));
            }

            echo '<!-- This site is optimized with the SEOPlugin -->' . "\n";
            
            // Basic meta tags
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
            
            // Robots meta
            if ($robots_meta) {
                echo '<meta name="robots" content="' . esc_attr($robots_meta) . '">' . "\n";
            }
            
            // Meta keywords
            if ($meta_keywords) {
                echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '">' . "\n";
            }
            
            // Open Graph tags
            echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
            
            if ($og_image_url) {
                $image_width = $meta['width'] ?? '';
                $image_height = $meta['height'] ?? '';
                echo '<meta property="og:image" content="' . esc_url($og_image_url) . '">' . "\n";
                echo '<meta property="og:image:width" content="' . $image_width . '">' . "\n";
                echo '<meta property="og:image:height" content="' . $image_height . '">' . "\n";
                echo '<meta property="og:image:alt" content="' . esc_attr($title) . '">' . "\n";
            }

            // Twitter Card tags
            echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
            if ($og_image_url) {
                echo '<meta name="twitter:image" content="' . esc_url($og_image_url) . '">' . "\n";
                echo '<meta name="twitter:image:alt" content="' . esc_attr($title) . '">' . "\n";
            }
            
            // Additional SEO meta tags
            echo '<meta name="author" content="' . esc_attr(get_the_author_meta('display_name', $post->post_author)) . '">' . "\n";
            echo '<meta name="article:published_time" content="' . esc_attr(get_the_date('c', $post->ID)) . '">' . "\n";
            echo '<meta name="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $post->ID)) . '">' . "\n";
            echo '<meta name="article:author" content="' . esc_attr(get_the_author_meta('display_name', $post->post_author)) . '">' . "\n";
        }
    }

    // Register sitemap rewrite rule
    public function register_sitemap() {
        add_rewrite_rule(
            'sitemap\.xml$',
            'index.php?seoplugin_sitemap=1',
            'top'
        );
        add_filter( 'query_vars', function( $vars ) {
            $vars[] = 'seoplugin_sitemap';
            return $vars;
        } );
        add_action( 'template_redirect', [ $this, 'render_sitemap' ] );
    }

    // Render XML sitemap
    public function render_sitemap() {
        if ( get_query_var( 'seoplugin_sitemap' ) ) {
            header( 'Content-Type: text/xml' );
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

            // Get all public post types
            $post_types = get_post_types(['public' => true], 'names');
            
            foreach ( $post_types as $post_type ) {
                $posts = get_posts( [ 
                    'post_type' => $post_type, 
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ] );
                
                foreach ( $posts as $post ) {
                    // Check if post should be indexed
                    $robots_meta = get_post_meta($post->ID, '_seoplugin_robots_meta', true);
                    if ($robots_meta && (strpos($robots_meta, 'noindex') !== false)) {
                        continue; // Skip noindex posts
                    }
                    
                    echo '<url>' . "\n";
                    echo '<loc>' . esc_url( get_permalink( $post->ID ) ) . '</loc>' . "\n";
                    echo '<lastmod>' . get_the_modified_date( 'c', $post->ID ) . '</lastmod>' . "\n";
                    echo '<changefreq>weekly</changefreq>' . "\n";
                    echo '<priority>0.8</priority>' . "\n";
                    
                    // Add featured image if exists
                    $thumbnail_id = get_post_thumbnail_id($post->ID);
                    if ($thumbnail_id) {
                        $image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
                        if ($image_url) {
                            echo '<image:image>' . "\n";
                            echo '<image:loc>' . esc_url($image_url) . '</image:loc>' . "\n";
                            echo '<image:title>' . esc_html(get_the_title($post->ID)) . '</image:title>' . "\n";
                            echo '</image:image>' . "\n";
                        }
                    }
                    
                    echo '</url>' . "\n";
                }
            }

            // Add categories and tags
            $categories = get_categories(['hide_empty' => true]);
            foreach ($categories as $category) {
                echo '<url>' . "\n";
                echo '<loc>' . esc_url(get_category_link($category->term_id)) . '</loc>' . "\n";
                echo '<lastmod>' . date('c') . '</lastmod>' . "\n";
                echo '<changefreq>weekly</changefreq>' . "\n";
                echo '<priority>0.6</priority>' . "\n";
                echo '</url>' . "\n";
            }

            $tags = get_tags(['hide_empty' => true]);
            foreach ($tags as $tag) {
                echo '<url>' . "\n";
                echo '<loc>' . esc_url(get_tag_link($tag->term_id)) . '</loc>' . "\n";
                echo '<lastmod>' . date('c') . '</lastmod>' . "\n";
                echo '<changefreq>monthly</changefreq>' . "\n";
                echo '<priority>0.4</priority>' . "\n";
                echo '</url>' . "\n";
            }

            echo '</urlset>';
            exit;
        }
    }

    // Render shortcode
    public function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'keywords' => __( 'SEO, WordPress, Optimization', 'seoplugin' ),
        ], $atts, 'seoplugin_keywords' );

        ob_start();
        ?>
        <div class="seoplugin-keywords">
            <p><?php esc_html_e( 'Suggested Keywords: ', 'seoplugin' ); ?><?php echo esc_html( $atts['keywords'] ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    // Override the default <title></title>
    public function override_title($title) {
        if (is_singular()) {
            $custom_title = get_post_meta(get_the_ID(), '_seoplugin_meta_title', true);
            if (!empty($custom_title)) {
                $title['title'] = $custom_title;
            }
        }
        return $title;
    }

    // Add JSON-LD structured data
    public function add_jsonld_snippet() {
        if (is_singular()) {
            global $post;

            $title = get_post_meta($post->ID, '_seoplugin_meta_title', true) ?: get_the_title($post->ID);
            $description = get_post_meta($post->ID, '_seoplugin_meta_description', true) ?: get_bloginfo('description');
            $og_image_id = get_post_meta($post->ID, '_seoplugin_og_image_id', true);
            $schema_type = get_post_meta($post->ID, '_seoplugin_schema_type', true) ?: 'Article';
            $url = get_permalink($post);
            
            //image script
            $image_url = '';
            $image_caption = '';
            $image_width = '';
            $image_height = '';
            if ($og_image_id && is_numeric($og_image_id)) {
                $image_url = wp_get_attachment_image_url($og_image_id, 'full');

                $image_post = get_post($og_image_id);
                $image_caption = $image_post->post_excerpt ?? '';

                $meta = wp_get_attachment_metadata($og_image_id);
                $image_width = $meta['width'] ?? '';
                $image_height = $meta['height'] ?? '';
            }
            $site_name = get_bloginfo('name');
            $site_url = home_url('/');
            $language = get_bloginfo('language') ?: 'en-US';

            $graph = [
                [
                    "@type" => $schema_type,
                    "@id" => $url,
                    "url" => $url,
                    "name" => $title,
                    "isPartOf" => ["@id" => "{$site_url}#website"],
                    "about" => ["@id" => "{$site_url}#organization"],
                    "primaryImageOfPage" => ["@id" => "{$url}#primaryimage"],
                    "image" => ["@id" => "{$url}#primaryimage"],
                    "thumbnailUrl" => $image_url,
                    "datePublished" => get_the_date('c', $post),
                    "dateModified" => get_the_modified_date('c', $post),
                    "description" => $description,
                    "breadcrumb" => ["@id" => "{$url}#breadcrumb"],
                    "inLanguage" => $language,
                    "potentialAction" => [
                        ["@type" => "ReadAction", "target" => [$url]]
                    ]
                ],
                [
                    "@type" => "ImageObject",
                    "inLanguage" => $language,
                    "@id" => "{$url}#primaryimage",
                    "url" => $image_url,
                    "contentUrl" => $image_url,
                    "width" => $image_width,
                    "height" => $image_height,
                    "caption" => $image_caption
                ],
                [
                    "@type" => "BreadcrumbList",
                    "@id" => "{$url}#breadcrumb",
                    "itemListElement" => [
                        [
                            "@type" => "ListItem",
                            "position" => 1,
                            "name" => "Home",
                            "item" => $site_url
                        ],
                        [
                            "@type" => "ListItem",
                            "position" => 2,
                            "name" => $title,
                            "item" => $url
                        ]
                    ]
                ],
                [
                    "@type" => "WebSite",
                    "@id" => "{$site_url}#website",
                    "url" => $site_url,
                    "name" => $site_name,
                    "description" => $description,
                    "publisher" => ["@id" => "{$site_url}#organization"],
                    "potentialAction" => [
                        [
                            "@type" => "SearchAction",
                            "target" => [
                                "@type" => "EntryPoint",
                                "urlTemplate" => "{$site_url}?s={search_term_string}"
                            ],
                            "query-input" => [
                                "@type" => "PropertyValueSpecification",
                                "valueRequired" => true,
                                "valueName" => "search_term_string"
                            ]
                        ]
                    ],
                    "inLanguage" => $language
                ],
                [
                    "@type" => "Organization",
                    "@id" => "{$site_url}#organization",
                    "name" => $site_name,
                    "url" => $site_url,
                    "logo" => [
                        "@type" => "ImageObject",
                        "inLanguage" => $language,
                        "@id" => "{$site_url}#/schema/logo/image/",
                        "url" => get_site_icon_url(),
                        "contentUrl" => get_site_icon_url(),
                        "width" => 512,
                        "height" => 512,
                        "caption" => $site_name
                    ],
                    "image" => ["@id" => "{$site_url}#/schema/logo/image/"],
                    "sameAs" => [] // Optional: Add social links here
                ]
            ];

            $jsonld = [
                "@context" => "https://schema.org",
                "@graph" => $graph
            ];

            echo '<script type="application/ld+json" class="seoplugin-schema-graph">' .
                 wp_json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
                 '</script>' . "\n";
            echo '<!-- SEOPlugin -->' . "\n";
        }
    }

    // Site domain URL shortcode
    public function site_domain_url_shortcode() {
        $site_url = site_url();
        // Remove http:// or https://
        $url_no_protocol = preg_replace('#^https?://#', '', $site_url);
        // Remove existing www. and prepend it manually
        $custom_url = 'www.' . preg_replace('/^www\./', '', $url_no_protocol);
        return esc_html($custom_url);
    }

    // Breadcrumbs shortcode
    public function breadcrumbs_shortcode($atts) {
        $atts = shortcode_atts([
            'separator' => ' > ',
            'home_text' => 'Home',
            'show_current' => 'true'
        ], $atts, 'seoplugin_breadcrumbs');

        if (is_front_page()) {
            return '';
        }

        $breadcrumbs = [];
        $separator = esc_html($atts['separator']);
        $home_text = esc_html($atts['home_text']);
        $show_current = $atts['show_current'] === 'true';

        // Add home link
        $breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">' . $home_text . '</a>';

        if (is_category() || is_single()) {
            if (is_single()) {
                $categories = get_the_category();
                if ($categories) {
                    $category = $categories[0];
                    $breadcrumbs[] = '<a href="' . esc_url(get_category_link($category->term_id)) . '">' . esc_html($category->name) . '</a>';
                }
                if ($show_current) {
                    $breadcrumbs[] = '<span class="current">' . get_the_title() . '</span>';
                }
            } else {
                $breadcrumbs[] = '<span class="current">' . single_cat_title('', false) . '</span>';
            }
        } elseif (is_page()) {
            $page_id = get_the_ID();
            $ancestors = get_post_ancestors($page_id);
            if ($ancestors) {
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor) {
                    $breadcrumbs[] = '<a href="' . esc_url(get_permalink($ancestor)) . '">' . esc_html(get_the_title($ancestor)) . '</a>';
                }
            }
            if ($show_current) {
                $breadcrumbs[] = '<span class="current">' . get_the_title() . '</span>';
            }
        } elseif (is_tag()) {
            $breadcrumbs[] = '<span class="current">' . single_tag_title('', false) . '</span>';
        } elseif (is_archive()) {
            $breadcrumbs[] = '<span class="current">' . get_the_archive_title() . '</span>';
        }

        return '<nav class="seoplugin-breadcrumbs" aria-label="Breadcrumb">' . implode($separator, $breadcrumbs) . '</nav>';
    }
}

new SEOPlugin_Public();