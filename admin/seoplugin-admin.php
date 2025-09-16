<?php
/**
 * SEOPlugin Admin Functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SEOPlugin_Admin {
    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'save_post_meta' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_assets' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_seoplugin_regen_og_custom', [ $this, 'ajax_regen_og_custom' ] );
        add_action( 'wp_ajax_seoplugin_ai_generate_title', [ $this, 'ajax_ai_generate_title' ] );
        add_action( 'wp_ajax_seoplugin_ai_generate_description', [ $this, 'ajax_ai_generate_description' ] );
        add_action( 'wp_ajax_seoplugin_ai_suggest_keywords', [ $this, 'ajax_ai_suggest_keywords' ] );
        add_action( 'wp_ajax_seoplugin_ai_analyze_content', [ $this, 'ajax_ai_analyze_content' ] );
        
        // Category/Term SEO hooks
        add_action( 'init', [ $this, 'init_term_seo' ] );
    }

    // Initialize term SEO for all public taxonomies
    public function init_term_seo() {
        $taxonomies = get_taxonomies( [ 'public' => true ], 'names' );
        
        foreach ( $taxonomies as $taxonomy ) {
            // Add form fields
            add_action( "{$taxonomy}_add_form_fields", [ $this, 'add_term_seo_fields' ] );
            add_action( "{$taxonomy}_edit_form_fields", [ $this, 'edit_term_seo_fields' ] );
            
            // Save term meta
            add_action( "created_{$taxonomy}", [ $this, 'save_term_seo_fields' ] );
            add_action( "edited_{$taxonomy}", [ $this, 'save_term_seo_fields' ] );
        }
    }

    // Add SEO fields to term add form
    public function add_term_seo_fields( $taxonomy ) {
        ?>
        <div class="form-field term-seo-wrap">
            <label for="seoplugin_meta_title"><?php _e( 'SEO Title', 'seoplugin' ); ?></label>
            <input type="text" id="seoplugin_meta_title" name="seoplugin_meta_title" class="frmtxt" maxlength="65" />
            <p class="description">
                <?php _e( 'Custom title for search engines (recommended: 30-65 characters)', 'seoplugin' ); ?>
                <br><span id="seoTitleCharCount">0</span>/65 characters
            </p>
        </div>

        <div class="form-field term-seo-wrap">
            <label for="seoplugin_meta_description"><?php _e( 'Meta Description', 'seoplugin' ); ?></label>
            <textarea id="seoplugin_meta_description" name="seoplugin_meta_description" class="frmtxt" rows="3" maxlength="160"></textarea>
            <p class="description">
                <?php _e( 'Brief description for search engines (recommended: 120-160 characters)', 'seoplugin' ); ?>
                <br><span id="seoDescriptionCharCount">0</span>/160 characters
            </p>
        </div>

        <div class="form-field term-seo-wrap">
            <label for="seoplugin_focus_keyword"><?php _e( 'Focus Keyword', 'seoplugin' ); ?></label>
            <input type="text" id="seoplugin_focus_keyword" name="seoplugin_focus_keyword" class="frmtxt" />
            <p class="description"><?php _e( 'Main keyword to optimize this category for', 'seoplugin' ); ?></p>
        </div>

        <div class="form-field term-seo-wrap">
            <label for="seoplugin_og_image_id"><?php _e( 'Open Graph Image', 'seoplugin' ); ?></label>
            <input type="hidden" id="seoplugin_og_image_id" name="seoplugin_og_image_id" />
            <div id="seoplugin_og_image_preview"></div>
            <button type="button" id="seoplugin_og_image_button" class="button"><?php _e( 'Select Image', 'seoplugin' ); ?></button>
            <button type="button" id="seoplugin_remove_og_image" class="button" style="display:none;"><?php _e( 'Remove Image', 'seoplugin' ); ?></button>
            <p class="description"><?php _e( 'Image for social media sharing (recommended: 1200x630px)', 'seoplugin' ); ?></p>
        </div>

        <div class="form-field term-seo-wrap">
            <label for="seoplugin_robots_meta"><?php _e( 'Robots Meta', 'seoplugin' ); ?></label>
            <select id="seoplugin_robots_meta" name="seoplugin_robots_meta" class="frmtxt">
                <option value=""><?php _e( 'Default (index, follow)', 'seoplugin' ); ?></option>
                <option value="noindex,follow"><?php _e( 'No Index, Follow', 'seoplugin' ); ?></option>
                <option value="index,nofollow"><?php _e( 'Index, No Follow', 'seoplugin' ); ?></option>
                <option value="noindex,nofollow"><?php _e( 'No Index, No Follow', 'seoplugin' ); ?></option>
            </select>
            <p class="description"><?php _e( 'Control how search engines index this category', 'seoplugin' ); ?></p>
        </div>

        <div class="form-field term-seo-wrap">
            <label for="seoplugin_canonical_url"><?php _e( 'Canonical URL', 'seoplugin' ); ?></label>
            <input type="url" id="seoplugin_canonical_url" name="seoplugin_canonical_url" class="frmtxt" />
            <p class="description"><?php _e( 'Custom canonical URL (leave empty for default)', 'seoplugin' ); ?></p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Initialize character counters
            initCharCounter('seoplugin_meta_title', 65, 'seoTitleCharCount');
            initCharCounter('seoplugin_meta_description', 160, 'seoDescriptionCharCount');
            
            // Initialize media uploader
            initTermMediaUploader();
        });
        
        function initTermMediaUploader() {
            jQuery('#seoplugin_og_image_button').on('click', function(e) {
                e.preventDefault();
                
                const frame = wp.media({
                    title: 'Select Open Graph Image',
                    button: { text: 'Use This Image' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    jQuery('#seoplugin_og_image_id').val(attachment.id);
                    jQuery('#seoplugin_og_image_preview').html(
                        '<img src="' + attachment.url + '" style="max-width: 300px; height: auto; border-radius: 4px;" />'
                    );
                    jQuery('#seoplugin_remove_og_image').show();
                });
                
                frame.open();
            });
            
            jQuery('#seoplugin_remove_og_image').on('click', function(e) {
                e.preventDefault();
                jQuery('#seoplugin_og_image_id').val('');
                jQuery('#seoplugin_og_image_preview').html('');
                jQuery(this).hide();
            });
        }
        </script>
        <?php
    }

    // Add SEO fields to term edit form
    public function edit_term_seo_fields( $term ) {
        $meta_title = get_term_meta( $term->term_id, '_seoplugin_meta_title', true );
        $meta_description = get_term_meta( $term->term_id, '_seoplugin_meta_description', true );
        $focus_keyword = get_term_meta( $term->term_id, '_seoplugin_focus_keyword', true );
        $og_image_id = get_term_meta( $term->term_id, '_seoplugin_og_image_id', true );
        $robots_meta = get_term_meta( $term->term_id, '_seoplugin_robots_meta', true );
        $canonical_url = get_term_meta( $term->term_id, '_seoplugin_canonical_url', true );
        
        $og_image_url = '';
        if ( $og_image_id ) {
            $og_image_url = wp_get_attachment_image_url( $og_image_id, 'medium' );
        }
        ?>
        <tr class="form-field term-seo-wrap">
            <th scope="row">
                <label for="seoplugin_meta_title"><?php _e( 'SEO Title', 'seoplugin' ); ?></label>
            </th>
            <td>
                <input type="text" id="seoplugin_meta_title" name="seoplugin_meta_title" class="frmtxt" 
                       value="<?php echo esc_attr( $meta_title ); ?>" maxlength="65" />
                <p class="description">
                    <?php _e( 'Custom title for search engines (recommended: 30-65 characters)', 'seoplugin' ); ?>
                    <br><span id="seoTitleCharCount"><?php echo strlen( $meta_title ); ?></span>/65 characters
                </p>
            </td>
        </tr>

        <tr class="form-field term-seo-wrap">
            <th scope="row">
                <label for="seoplugin_meta_description"><?php _e( 'Meta Description', 'seoplugin' ); ?></label>
            </th>
            <td>
                <textarea id="seoplugin_meta_description" name="seoplugin_meta_description" class="frmtxt" 
                          rows="3" maxlength="160"><?php echo esc_textarea( $meta_description ); ?></textarea>
                <p class="description">
                    <?php _e( 'Brief description for search engines (recommended: 120-160 characters)', 'seoplugin' ); ?>
                    <br><span id="seoDescriptionCharCount"><?php echo strlen( $meta_description ); ?></span>/160 characters
                </p>
            </td>
        </tr>

        <tr class="form-field term-seo-wrap">
            <th scope="row">
                <label for="seoplugin_focus_keyword"><?php _e( 'Focus Keyword', 'seoplugin' ); ?></label>
            </th>
            <td>
                <input type="text" id="seoplugin_focus_keyword" name="seoplugin_focus_keyword" class="frmtxt" 
                       value="<?php echo esc_attr( $focus_keyword ); ?>" />
                <p class="description"><?php _e( 'Main keyword to optimize this category for', 'seoplugin' ); ?></p>
            </td>
        </tr>

        <tr class="form-field term-seo-wrap">
            <th scope="row">
                <label for="seoplugin_og_image_id"><?php _e( 'Open Graph Image', 'seoplugin' ); ?></label>
            </th>
            <td>
                <input type="hidden" id="seoplugin_og_image_id" name="seoplugin_og_image_id" 
                       value="<?php echo esc_attr( $og_image_id ); ?>" />
                <div id="seoplugin_og_image_preview">
                    <?php if ( $og_image_url ): ?>
                        <img src="<?php echo esc_url( $og_image_url ); ?>" 
                             style="max-width: 300px; height: auto; border-radius: 4px;" />
                    <?php endif; ?>
                </div>
                <button type="button" id="seoplugin_og_image_button" class="button">
                    <?php _e( 'Select Image', 'seoplugin' ); ?>
                </button>
                <button type="button" id="seoplugin_remove_og_image" class="button" 
                        style="<?php echo $og_image_id ? '' : 'display:none;'; ?>">
                    <?php _e( 'Remove Image', 'seoplugin' ); ?>
                </button>
                <p class="description"><?php _e( 'Image for social media sharing (recommended: 1200x630px)', 'seoplugin' ); ?></p>
            </td>
        </tr>

        <tr class="form-field term-seo-wrap">
            <th scope="row">
                <label for="seoplugin_robots_meta"><?php _e( 'Robots Meta', 'seoplugin' ); ?></label>
            </th>
            <td>
                <select id="seoplugin_robots_meta" name="seoplugin_robots_meta" class="frmtxt">
                    <option value="" <?php selected( $robots_meta, '' ); ?>><?php _e( 'Default (index, follow)', 'seoplugin' ); ?></option>
                    <option value="noindex,follow" <?php selected( $robots_meta, 'noindex,follow' ); ?>><?php _e( 'No Index, Follow', 'seoplugin' ); ?></option>
                    <option value="index,nofollow" <?php selected( $robots_meta, 'index,nofollow' ); ?>><?php _e( 'Index, No Follow', 'seoplugin' ); ?></option>
                    <option value="noindex,nofollow" <?php selected( $robots_meta, 'noindex,nofollow' ); ?>><?php _e( 'No Index, No Follow', 'seoplugin' ); ?></option>
                </select>
                <p class="description"><?php _e( 'Control how search engines index this category', 'seoplugin' ); ?></p>
            </td>
        </tr>

        <tr class="form-field term-seo-wrap">
            <th scope="row">
                <label for="seoplugin_canonical_url"><?php _e( 'Canonical URL', 'seoplugin' ); ?></label>
            </th>
            <td>
                <input type="url" id="seoplugin_canonical_url" name="seoplugin_canonical_url" class="frmtxt" 
                       value="<?php echo esc_attr( $canonical_url ); ?>" />
                <p class="description"><?php _e( 'Custom canonical URL (leave empty for default)', 'seoplugin' ); ?></p>
            </td>
        </tr>

        <tr class="form-field term-seo-wrap">
            <th scope="row"><?php _e( 'SEO Preview', 'seoplugin' ); ?></th>
            <td>
                <div class="google-view">
                    <div class="google-wrap-content">
                        <div class="header-logo">
                            <div class="divddercolunm">
                                <div class="google-logo">
                                    <img class="logo" src="<?php echo get_site_icon_url( 32 ) ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDJMMTMuMDkgOC4yNkwyMCA5TDEzLjA5IDE1Ljc0TDEyIDIyTDEwLjkxIDE1Ljc0TDQgOUwxMC45MSA4LjI2TDEyIDJaIiBmaWxsPSIjNDI4NUY0Ii8+Cjwvc3ZnPgo='; ?>" alt="Site Icon">
                                </div>
                                <div class="google-site">
                                    <div class="google-site-domain"><?php echo esc_html( parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
                                    <div class="site-down-color"><?php echo esc_url( get_term_link( $term ) ); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="wrp-google-title">
                            <h3 class="google-title">
                                <a href="#" class="google-title3" id="preview-title">
                                    <?php echo esc_html( $meta_title ?: $term->name ); ?>
                                </a>
                            </h3>
                        </div>
                        <div class="wrp-google-decription">
                            <span id="preview-description">
                                <?php echo esc_html( $meta_description ?: ( $term->description ?: 'Browse our ' . $term->name . ' content.' ) ); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </td>
        </tr>

        <script>
        jQuery(document).ready(function($) {
            // Initialize character counters
            initCharCounter('seoplugin_meta_title', 65, 'seoTitleCharCount');
            initCharCounter('seoplugin_meta_description', 160, 'seoDescriptionCharCount');
            
            // Initialize media uploader
            initTermMediaUploader();
            
            // Update preview on input
            $('#seoplugin_meta_title').on('input', function() {
                const title = $(this).val() || '<?php echo esc_js( $term->name ); ?>';
                $('#preview-title').text(title);
            });
            
            $('#seoplugin_meta_description').on('input', function() {
                const desc = $(this).val() || '<?php echo esc_js( $term->description ?: 'Browse our ' . $term->name . ' content.' ); ?>';
                $('#preview-description').text(desc);
            });
        });
        
        function initTermMediaUploader() {
            jQuery('#seoplugin_og_image_button').on('click', function(e) {
                e.preventDefault();
                
                const frame = wp.media({
                    title: 'Select Open Graph Image',
                    button: { text: 'Use This Image' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    jQuery('#seoplugin_og_image_id').val(attachment.id);
                    jQuery('#seoplugin_og_image_preview').html(
                        '<img src="' + attachment.url + '" style="max-width: 300px; height: auto; border-radius: 4px;" />'
                    );
                    jQuery('#seoplugin_remove_og_image').show();
                });
                
                frame.open();
            });
            
            jQuery('#seoplugin_remove_og_image').on('click', function(e) {
                e.preventDefault();
                jQuery('#seoplugin_og_image_id').val('');
                jQuery('#seoplugin_og_image_preview').html('');
                jQuery(this).hide();
            });
        }
        </script>
        <?php
    }

    // Save term SEO fields
    public function save_term_seo_fields( $term_id ) {
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }

        $fields = [
            '_seoplugin_meta_title' => 'seoplugin_meta_title',
            '_seoplugin_meta_description' => 'seoplugin_meta_description',
            '_seoplugin_focus_keyword' => 'seoplugin_focus_keyword',
            '_seoplugin_og_image_id' => 'seoplugin_og_image_id',
            '_seoplugin_robots_meta' => 'seoplugin_robots_meta',
            '_seoplugin_canonical_url' => 'seoplugin_canonical_url'
        ];

        foreach ( $fields as $meta_key => $post_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                $value = sanitize_text_field( $_POST[ $post_key ] );
                if ( $post_key === 'seoplugin_meta_description' ) {
                    $value = sanitize_textarea_field( $_POST[ $post_key ] );
                } elseif ( $post_key === 'seoplugin_canonical_url' ) {
                    $value = esc_url_raw( $_POST[ $post_key ] );
                } elseif ( $post_key === 'seoplugin_og_image_id' ) {
                    $value = absint( $_POST[ $post_key ] );
                }
                
                if ( ! empty( $value ) ) {
                    update_term_meta( $term_id, $meta_key, $value );
                } else {
                    delete_term_meta( $term_id, $meta_key );
                }
            }
        }
    }

    // Enqueue assets
    public function enqueue_assets() {
        wp_enqueue_style(
            'seoplugin-styles',
            SEOPLUGIN_URL . 'assets/css/seoplugin.css',
            [],
            SEOPLUGIN_VERSION
        );
    }

    // Admin enqueue assets
    public function admin_enqueue_assets( $hook ) {
        // Enqueue on post edit pages and term edit pages
        if ( in_array( $hook, [ 'post.php', 'post-new.php', 'edit-tags.php', 'term.php' ] ) || 
             strpos( $hook, 'seoplugin' ) !== false ) {
            
            wp_enqueue_media();
            wp_enqueue_style(
                'seoplugin-admin-styles',
                SEOPLUGIN_URL . 'assets/css/seoplugin.css',
                [],
                SEOPLUGIN_VERSION
            );

            wp_enqueue_script(
                'seoplugin-admin-scripts',
                SEOPLUGIN_URL . 'assets/js/seoplugin.js',
                [ 'jquery', 'media-upload', 'media-views' ],
                SEOPLUGIN_VERSION,
                true
            );

            wp_localize_script( 'seoplugin-admin-scripts', 'seoplugin_ajax', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'seoplugin_nonce' )
            ] );
        }
    }

    // Add meta boxes
    public function add_meta_boxes() {
        $post_types = get_post_types( [ 'public' => true ], 'names' );
        
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'seoplugin_meta_box',
                __( 'SEO Settings', 'seoplugin' ),
                [ $this, 'render_meta_box' ],
                $post_type,
                'normal',
                'high'
            );
        }
    }

    // Render meta box
    public function render_meta_box( $post ) {
        wp_nonce_field( 'seoplugin_meta_box', 'seoplugin_meta_box_nonce' );

        $meta_title = get_post_meta( $post->ID, '_seoplugin_meta_title', true );
        $meta_description = get_post_meta( $post->ID, '_seoplugin_meta_description', true );
        $focus_keyword = get_post_meta( $post->ID, '_seoplugin_focus_keyword', true );
        $og_image_id = get_post_meta( $post->ID, '_seoplugin_og_image_id', true );
        $robots_meta = get_post_meta( $post->ID, '_seoplugin_robots_meta', true );
        $canonical_url = get_post_meta( $post->ID, '_seoplugin_canonical_url', true );
        $meta_keywords = get_post_meta( $post->ID, '_seoplugin_meta_keywords', true );
        $schema_type = get_post_meta( $post->ID, '_seoplugin_schema_type', true );

        $og_image_url = '';
        if ( $og_image_id ) {
            $og_image_url = wp_get_attachment_image_url( $og_image_id, 'medium' );
        }
        ?>
        <div class="seoplugin_eseo">
            <!-- SEO Analysis Section -->
            <div class="seo-analysis-section">
                <h3><?php _e( 'SEO Analysis', 'seoplugin' ); ?></h3>
                <div class="seo-score-container">
                    <div class="seo-score-circle">
                        <span id="seo-score">0</span>
                        <small>/ 100</small>
                    </div>
                    <div class="seo-analysis-details">
                        <div class="seo-item">
                            <span class="seo-icon">📝</span>
                            <span class="seo-text"><?php _e( 'SEO Title Length', 'seoplugin' ); ?></span>
                            <span class="seo-status" id="title-status">❌</span>
                        </div>
                        <div class="seo-item">
                            <span class="seo-icon">📄</span>
                            <span class="seo-text"><?php _e( 'Meta Description Length', 'seoplugin' ); ?></span>
                            <span class="seo-status" id="desc-status">❌</span>
                        </div>
                        <div class="seo-item">
                            <span class="seo-icon">🖼️</span>
                            <span class="seo-text"><?php _e( 'Featured Image Set', 'seoplugin' ); ?></span>
                            <span class="seo-status" id="image-status">❌</span>
                        </div>
                        <div class="seo-item">
                            <span class="seo-icon">🎯</span>
                            <span class="seo-text"><?php _e( 'Focus Keyword Usage', 'seoplugin' ); ?></span>
                            <span class="seo-status" id="keyword-status">❌</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Basic SEO Fields -->
            <div>
                <label class="control-label" for="seoplugin_meta_title"><?php _e( 'SEO Title', 'seoplugin' ); ?></label>
                <input type="text" id="seoplugin_meta_title" name="seoplugin_meta_title" class="widefat frmtxt" 
                       value="<?php echo esc_attr( $meta_title ); ?>" maxlength="65" />
                <p class="description">
                    <?php _e( 'Custom title for search engines (recommended: 30-65 characters)', 'seoplugin' ); ?>
                    <br><span id="seoTitleCharCount"><?php echo strlen( $meta_title ); ?></span>/65 characters
                </p>
            </div>

            <div>
                <label class="control-label" for="seoplugin_meta_description"><?php _e( 'Meta Description', 'seoplugin' ); ?></label>
                <textarea id="seoplugin_meta_description" name="seoplugin_meta_description" class="widefat frmtxt" 
                          rows="3" maxlength="160"><?php echo esc_textarea( $meta_description ); ?></textarea>
                <p class="description">
                    <?php _e( 'Brief description for search engines (recommended: 120-160 characters)', 'seoplugin' ); ?>
                    <br><span id="seoDescriptionCharCount"><?php echo strlen( $meta_description ); ?></span>/160 characters
                </p>
            </div>

            <div>
                <label class="control-label" for="seoplugin_focus_keyword"><?php _e( 'Focus Keyword', 'seoplugin' ); ?></label>
                <input type="text" id="seoplugin_focus_keyword" name="seoplugin_focus_keyword" class="widefat frmtxt" 
                       value="<?php echo esc_attr( $focus_keyword ); ?>" />
                <p class="description"><?php _e( 'Main keyword to optimize this content for', 'seoplugin' ); ?></p>
            </div>

            <!-- Open Graph Image -->
            <div>
                <label class="control-label"><?php _e( 'Open Graph Image', 'seoplugin' ); ?></label>
                <input type="hidden" id="seoplugin_og_image_id" name="seoplugin_og_image_id" 
                       value="<?php echo esc_attr( $og_image_id ); ?>" />
                <div id="seoplugin_og_image_preview" class="seoplugin_og_image_preview">
                    <?php if ( $og_image_url ): ?>
                        <img src="<?php echo esc_url( $og_image_url ); ?>" 
                             style="width:527px; height:352px; object-fit:cover;" />
                    <?php endif; ?>
                </div>
                <button type="button" id="seoplugin_og_image_button" class="button">
                    <?php _e( 'Select OG Image', 'seoplugin' ); ?>
                </button>
                <p class="description"><?php _e( 'Image for social media sharing (recommended: 1200x630px)', 'seoplugin' ); ?></p>
            </div>

            <!-- Google Preview -->
            <div>
                <label class="control-label"><?php _e( 'Google Preview', 'seoplugin' ); ?></label>
                <div class="google-view">
                    <div class="google-wrap-content">
                        <div class="header-logo">
                            <div class="divddercolunm">
                                <div class="google-logo">
                                    <img class="logo" src="<?php echo get_site_icon_url( 32 ) ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEyIDJMMTMuMDkgOC4yNkwyMCA5TDEzLjA5IDE1Ljc0TDEyIDIyTDEwLjkxIDE1Ljc0TDQgOUwxMC45MSA4LjI2TDEyIDJaIiBmaWxsPSIjNDI4NUY0Ii8+Cjwvc3ZnPgo='; ?>" alt="Site Icon">
                                </div>
                                <div class="google-site">
                                    <div class="google-site-domain"><?php echo esc_html( parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
                                    <div class="site-down-color"><?php echo esc_url( get_permalink( $post->ID ) ?: home_url() ); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="wrp-google-title">
                            <h3 class="google-title">
                                <a href="#" class="google-title3">
                                    <?php echo esc_html( $meta_title ?: get_the_title( $post->ID ) ); ?>
                                </a>
                            </h3>
                        </div>
                        <div class="wrp-google-decription">
                            <?php echo esc_html( $meta_description ?: get_the_excerpt( $post->ID ) ?: 'No description available.' ); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Media Previews -->
            <div class="social-previews">
                <h3><?php _e( 'Social Media Previews', 'seoplugin' ); ?></h3>
                <div class="social-tabs">
                    <button type="button" class="social-tab active" data-tab="facebook"><?php _e( 'Facebook', 'seoplugin' ); ?></button>
                    <button type="button" class="social-tab" data-tab="x"><?php _e( 'X (Twitter)', 'seoplugin' ); ?></button>
                    <button type="button" class="social-tab" data-tab="linkedin"><?php _e( 'LinkedIn', 'seoplugin' ); ?></button>
                </div>
                
                <div id="facebook-preview" class="social-preview active">
                    <div class="social-card">
                        <div class="social-image">
                            <?php if ( $og_image_url ): ?>
                                <img src="<?php echo esc_url( $og_image_url ); ?>" alt="Preview" />
                            <?php else: ?>
                                <div class="no-image"><?php _e( 'No Image', 'seoplugin' ); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="social-content">
                            <div class="social-url"><?php echo esc_html( parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
                            <div class="social-title"><?php echo esc_html( $meta_title ?: get_the_title( $post->ID ) ); ?></div>
                            <div class="social-description"><?php echo esc_html( $meta_description ?: 'Your description here...' ); ?></div>
                        </div>
                    </div>
                </div>
                
                <div id="x-preview" class="social-preview">
                    <div class="x-card">
                        <div class="x-image">
                            <?php if ( $og_image_url ): ?>
                                <img src="<?php echo esc_url( $og_image_url ); ?>" alt="Preview" />
                            <?php else: ?>
                                <div class="no-image"><?php _e( 'No Image', 'seoplugin' ); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="x-content">
                            <div class="x-url"><?php echo esc_html( parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
                            <div class="x-title"><?php echo esc_html( $meta_title ?: get_the_title( $post->ID ) ); ?></div>
                            <div class="x-description"><?php echo esc_html( $meta_description ?: 'Your description here...' ); ?></div>
                        </div>
                    </div>
                </div>
                
                <div id="linkedin-preview" class="social-preview">
                    <div class="linkedin-card">
                        <div class="linkedin-image">
                            <?php if ( $og_image_url ): ?>
                                <img src="<?php echo esc_url( $og_image_url ); ?>" alt="Preview" />
                            <?php else: ?>
                                <div class="no-image"><?php _e( 'No Image', 'seoplugin' ); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="linkedin-content">
                            <div class="linkedin-url"><?php echo esc_html( parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
                            <div class="linkedin-title"><?php echo esc_html( $meta_title ?: get_the_title( $post->ID ) ); ?></div>
                            <div class="linkedin-description"><?php echo esc_html( $meta_description ?: 'Your description here...' ); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI SEO Assistant -->
            <?php if ( get_option( 'seoplugin_ai_enabled', false ) ): ?>
            <div class="ai-seo-assistant">
                <h3><?php _e( '🤖 AI SEO Assistant', 'seoplugin' ); ?></h3>
                <div class="ai-controls">
                    <button type="button" id="ai-generate-title" class="button"><?php _e( 'Generate Title', 'seoplugin' ); ?></button>
                    <button type="button" id="ai-generate-description" class="button"><?php _e( 'Generate Description', 'seoplugin' ); ?></button>
                    <button type="button" id="ai-suggest-keywords" class="button"><?php _e( 'Suggest Keywords', 'seoplugin' ); ?></button>
                    <button type="button" id="ai-analyze-content" class="button"><?php _e( 'Analyze Content', 'seoplugin' ); ?></button>
                </div>
                <div id="ai-loading" class="ai-loading" style="display:none;">
                    <div class="spinner"></div>
                    <span><?php _e( 'AI is working...', 'seoplugin' ); ?></span>
                </div>
                <div id="ai-suggestions" class="ai-suggestions" style="display:none;">
                    <h4><?php _e( 'AI Suggestions', 'seoplugin' ); ?></h4>
                    <div id="ai-suggestion-content"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Advanced SEO Options -->
            <div class="advanced-seo-options">
                <h3><?php _e( 'Advanced SEO Options', 'seoplugin' ); ?></h3>
                
                <div>
                    <label class="control-label" for="seoplugin_meta_keywords"><?php _e( 'Meta Keywords', 'seoplugin' ); ?></label>
                    <input type="text" id="seoplugin_meta_keywords" name="seoplugin_meta_keywords" class="widefat frmtxt" 
                           value="<?php echo esc_attr( $meta_keywords ); ?>" />
                    <span class="description"><?php _e( 'Comma-separated keywords (optional, not used by most search engines)', 'seoplugin' ); ?></span>
                </div>

                <div>
                    <label class="control-label" for="seoplugin_robots_meta"><?php _e( 'Robots Meta', 'seoplugin' ); ?></label>
                    <select id="seoplugin_robots_meta" name="seoplugin_robots_meta" class="frmtxt">
                        <option value="" <?php selected( $robots_meta, '' ); ?>><?php _e( 'Default (index, follow)', 'seoplugin' ); ?></option>
                        <option value="noindex,follow" <?php selected( $robots_meta, 'noindex,follow' ); ?>><?php _e( 'No Index, Follow', 'seoplugin' ); ?></option>
                        <option value="index,nofollow" <?php selected( $robots_meta, 'index,nofollow' ); ?>><?php _e( 'Index, No Follow', 'seoplugin' ); ?></option>
                        <option value="noindex,nofollow" <?php selected( $robots_meta, 'noindex,nofollow' ); ?>><?php _e( 'No Index, No Follow', 'seoplugin' ); ?></option>
                    </select>
                    <span class="description"><?php _e( 'Control how search engines crawl and index this content', 'seoplugin' ); ?></span>
                </div>

                <div>
                    <label class="control-label" for="seoplugin_canonical_url"><?php _e( 'Canonical URL', 'seoplugin' ); ?></label>
                    <input type="url" id="seoplugin_canonical_url" name="seoplugin_canonical_url" class="widefat frmtxt" 
                           value="<?php echo esc_attr( $canonical_url ); ?>" />
                    <span class="description"><?php _e( 'Custom canonical URL (leave empty for default)', 'seoplugin' ); ?></span>
                </div>

                <div>
                    <label class="control-label" for="seoplugin_schema_type"><?php _e( 'Schema Type', 'seoplugin' ); ?></label>
                    <select id="seoplugin_schema_type" name="seoplugin_schema_type" class="frmtxt">
                        <option value="Article" <?php selected( $schema_type, 'Article' ); ?>><?php _e( 'Article', 'seoplugin' ); ?></option>
                        <option value="BlogPosting" <?php selected( $schema_type, 'BlogPosting' ); ?>><?php _e( 'Blog Posting', 'seoplugin' ); ?></option>
                        <option value="NewsArticle" <?php selected( $schema_type, 'NewsArticle' ); ?>><?php _e( 'News Article', 'seoplugin' ); ?></option>
                        <option value="WebPage" <?php selected( $schema_type, 'WebPage' ); ?>><?php _e( 'Web Page', 'seoplugin' ); ?></option>
                        <option value="Product" <?php selected( $schema_type, 'Product' ); ?>><?php _e( 'Product', 'seoplugin' ); ?></option>
                        <option value="Event" <?php selected( $schema_type, 'Event' ); ?>><?php _e( 'Event', 'seoplugin' ); ?></option>
                    </select>
                    <span class="description"><?php _e( 'Schema.org structured data type for this content', 'seoplugin' ); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    // Save post meta
    public function save_post_meta( $post_id ) {
        if ( ! isset( $_POST['seoplugin_meta_box_nonce'] ) || 
             ! wp_verify_nonce( $_POST['seoplugin_meta_box_nonce'], 'seoplugin_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            '_seoplugin_meta_title' => 'seoplugin_meta_title',
            '_seoplugin_meta_description' => 'seoplugin_meta_description',
            '_seoplugin_focus_keyword' => 'seoplugin_focus_keyword',
            '_seoplugin_og_image_id' => 'seoplugin_og_image_id',
            '_seoplugin_robots_meta' => 'seoplugin_robots_meta',
            '_seoplugin_canonical_url' => 'seoplugin_canonical_url',
            '_seoplugin_meta_keywords' => 'seoplugin_meta_keywords',
            '_seoplugin_schema_type' => 'seoplugin_schema_type'
        ];

        foreach ( $fields as $meta_key => $post_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                $value = sanitize_text_field( $_POST[ $post_key ] );
                if ( $post_key === 'seoplugin_meta_description' ) {
                    $value = sanitize_textarea_field( $_POST[ $post_key ] );
                } elseif ( $post_key === 'seoplugin_canonical_url' ) {
                    $value = esc_url_raw( $_POST[ $post_key ] );
                } elseif ( $post_key === 'seoplugin_og_image_id' ) {
                    $value = absint( $_POST[ $post_key ] );
                }
                
                if ( ! empty( $value ) ) {
                    update_post_meta( $post_id, $meta_key, $value );
                } else {
                    delete_post_meta( $post_id, $meta_key );
                }
            }
        }
    }

    // Add admin menu
    public function add_admin_menu() {
        add_options_page(
            __( 'SEOPlugin Settings', 'seoplugin' ),
            __( 'SEOPlugin', 'seoplugin' ),
            'manage_options',
            'seoplugin-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    // Register settings
    public function register_settings() {
        // General Settings
        register_setting( 'seoplugin_general', 'seoplugin_homepage_title' );
        register_setting( 'seoplugin_general', 'seoplugin_homepage_description' );
        register_setting( 'seoplugin_general', 'seoplugin_separator' );
        register_setting( 'seoplugin_general', 'seoplugin_default_meta_description' );
        register_setting( 'seoplugin_general', 'seoplugin_default_og_image' );

        // Social Media Settings
        register_setting( 'seoplugin_social', 'seoplugin_facebook_app_id' );
        register_setting( 'seoplugin_social', 'seoplugin_x_username' );
        register_setting( 'seoplugin_social', 'seoplugin_linkedin_company_id' );
        register_setting( 'seoplugin_social', 'seoplugin_instagram_username' );
        register_setting( 'seoplugin_social', 'seoplugin_youtube_channel' );
        register_setting( 'seoplugin_social', 'seoplugin_tiktok_username' );

        // AI Assistant Settings
        register_setting( 'seoplugin_ai', 'seoplugin_ai_enabled' );
        register_setting( 'seoplugin_ai', 'seoplugin_gemini_api_key' );

        // Advanced Settings
        register_setting( 'seoplugin_advanced', 'seoplugin_xml_sitemap_enabled' );
        register_setting( 'seoplugin_advanced', 'seoplugin_breadcrumbs_enabled' );
        register_setting( 'seoplugin_advanced', 'seoplugin_noindex_categories' );
        register_setting( 'seoplugin_advanced', 'seoplugin_noindex_tags' );
        register_setting( 'seoplugin_advanced', 'seoplugin_noindex_archives' );
        register_setting( 'seoplugin_advanced', 'seoplugin_remove_category_base' );

        // Webmaster Tools Settings
        register_setting( 'seoplugin_webmaster', 'seoplugin_google_verification' );
        register_setting( 'seoplugin_webmaster', 'seoplugin_bing_verification' );
        register_setting( 'seoplugin_webmaster', 'seoplugin_yandex_verification' );
        register_setting( 'seoplugin_webmaster', 'seoplugin_pinterest_verification' );
        register_setting( 'seoplugin_webmaster', 'seoplugin_google_analytics' );
        register_setting( 'seoplugin_webmaster', 'seoplugin_google_tag_manager' );
    }

    // Render settings page
    public function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e( 'SEOPlugin Settings', 'seoplugin' ); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=seoplugin-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'General', 'seoplugin' ); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=social" class="nav-tab <?php echo $active_tab === 'social' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Social Media', 'seoplugin' ); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=ai" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'AI Assistant', 'seoplugin' ); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Advanced', 'seoplugin' ); ?>
                </a>
                <a href="?page=seoplugin-settings&tab=webmaster" class="nav-tab <?php echo $active_tab === 'webmaster' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Webmaster Tools', 'seoplugin' ); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ( $active_tab ) {
                    case 'general':
                        $this->render_general_settings();
                        break;
                    case 'social':
                        $this->render_social_settings();
                        break;
                    case 'ai':
                        $this->render_ai_settings();
                        break;
                    case 'advanced':
                        $this->render_advanced_settings();
                        break;
                    case 'webmaster':
                        $this->render_webmaster_settings();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    // Render general settings
    private function render_general_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'seoplugin_general' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Homepage Title', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_homepage_title" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_homepage_title', get_bloginfo( 'name' ) ) ); ?>" />
                        <p class="description"><?php _e( 'Custom title for your homepage in search results', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Homepage Description', 'seoplugin' ); ?></th>
                    <td>
                        <textarea name="seoplugin_homepage_description" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'seoplugin_homepage_description', get_bloginfo( 'description' ) ) ); ?></textarea>
                        <p class="description"><?php _e( 'Meta description for your homepage (recommended: 120-160 characters)', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Title Separator', 'seoplugin' ); ?></th>
                    <td>
                        <select name="seoplugin_separator">
                            <?php
                            $separators = [ '|' => '|', '-' => '-', '–' => '–', '—' => '—', '·' => '·', '»' => '»', '/' => '/' ];
                            $current_separator = get_option( 'seoplugin_separator', '|' );
                            foreach ( $separators as $value => $label ) {
                                echo '<option value="' . esc_attr( $value ) . '" ' . selected( $current_separator, $value, false ) . '>' . esc_html( $label ) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php _e( 'Character used to separate page title from site name', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Default Meta Description', 'seoplugin' ); ?></th>
                    <td>
                        <textarea name="seoplugin_default_meta_description" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'seoplugin_default_meta_description', 'Welcome to our site!' ) ); ?></textarea>
                        <p class="description"><?php _e( 'Fallback meta description when none is specified', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Default Open Graph Image', 'seoplugin' ); ?></th>
                    <td>
                        <div class="default-og-image-container">
                            <input type="hidden" id="seoplugin_default_og_image" name="seoplugin_default_og_image" 
                                   value="<?php echo esc_attr( get_option( 'seoplugin_default_og_image' ) ); ?>" />
                            <div class="image-preview" id="default-og-preview">
                                <?php
                                $default_og_image = get_option( 'seoplugin_default_og_image' );
                                if ( $default_og_image ) {
                                    $image_url = wp_get_attachment_image_url( $default_og_image, 'medium' );
                                    if ( $image_url ) {
                                        echo '<img src="' . esc_url( $image_url ) . '" alt="Default OG Image" />';
                                    }
                                } else {
                                    echo '<p>' . __( 'No default image selected', 'seoplugin' ) . '</p>';
                                }
                                ?>
                            </div>
                            <button type="button" id="select-default-og-image" class="button">
                                <?php _e( 'Select Default Image', 'seoplugin' ); ?>
                            </button>
                            <button type="button" id="remove-default-og-image" class="button" 
                                    style="<?php echo $default_og_image ? '' : 'display:none;'; ?>">
                                <?php _e( 'Remove Image', 'seoplugin' ); ?>
                            </button>
                        </div>
                        <p class="description"><?php _e( 'Default image for social media sharing when no specific image is set (recommended: 1200x630px)', 'seoplugin' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="seo-preview">
                <h3><?php _e( 'Homepage SEO Preview', 'seoplugin' ); ?></h3>
                <div class="seo-preview-title" id="homepage-preview-title">
                    <?php echo esc_html( get_option( 'seoplugin_homepage_title', get_bloginfo( 'name' ) ) ); ?>
                </div>
                <div class="seo-preview-url">
                    <?php echo esc_url( home_url() ); ?>
                </div>
                <div class="seo-preview-description" id="homepage-preview-description">
                    <?php echo esc_html( get_option( 'seoplugin_homepage_description', get_bloginfo( 'description' ) ) ); ?>
                </div>
            </div>
            
            <?php submit_button(); ?>
        </form>

        <script>
        jQuery(document).ready(function($) {
            // Update preview on input
            $('input[name="seoplugin_homepage_title"]').on('input', function() {
                $('#homepage-preview-title').text($(this).val() || '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>');
            });
            
            $('textarea[name="seoplugin_homepage_description"]').on('input', function() {
                $('#homepage-preview-description').text($(this).val() || '<?php echo esc_js( get_bloginfo( 'description' ) ); ?>');
            });
            
            // Default OG Image selector
            $('#select-default-og-image').on('click', function(e) {
                e.preventDefault();
                
                const frame = wp.media({
                    title: 'Select Default Open Graph Image',
                    button: { text: 'Use This Image' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                frame.on('select', function() {
                    const attachment = frame.state().get('selection').first().toJSON();
                    $('#seoplugin_default_og_image').val(attachment.id);
                    $('#default-og-preview').html('<img src="' + attachment.url + '" alt="Default OG Image" />');
                    $('#remove-default-og-image').show();
                });
                
                frame.open();
            });
            
            $('#remove-default-og-image').on('click', function(e) {
                e.preventDefault();
                $('#seoplugin_default_og_image').val('');
                $('#default-og-preview').html('<p><?php echo esc_js( __( 'No default image selected', 'seoplugin' ) ); ?></p>');
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    // Render social settings
    private function render_social_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'seoplugin_social' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Facebook App ID', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_facebook_app_id" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_facebook_app_id' ) ); ?>" />
                        <p class="description">
                            <?php _e( 'Facebook App ID for enhanced social sharing', 'seoplugin' ); ?>
                            <a href="https://developers.facebook.com/apps/" target="_blank"><?php _e( 'Get your App ID', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'X (Twitter) Username', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_x_username" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_x_username', get_option( 'seoplugin_twitter_username' ) ) ); ?>" 
                               placeholder="@username" />
                        <p class="description"><?php _e( 'Your X (formerly Twitter) username for Twitter Card attribution', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'LinkedIn Company ID', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_linkedin_company_id" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_linkedin_company_id' ) ); ?>" />
                        <p class="description"><?php _e( 'LinkedIn Company ID for enhanced LinkedIn sharing', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Instagram Username', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_instagram_username" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_instagram_username' ) ); ?>" 
                               placeholder="@username" />
                        <p class="description"><?php _e( 'Your Instagram username for social media integration', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'YouTube Channel URL', 'seoplugin' ); ?></th>
                    <td>
                        <input type="url" name="seoplugin_youtube_channel" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_youtube_channel' ) ); ?>" 
                               placeholder="https://www.youtube.com/channel/..." />
                        <p class="description"><?php _e( 'Full URL to your YouTube channel', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'TikTok Username', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_tiktok_username" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_tiktok_username' ) ); ?>" 
                               placeholder="@username" />
                        <p class="description"><?php _e( 'Your TikTok username for social media integration', 'seoplugin' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    // Render AI settings
    private function render_ai_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'seoplugin_ai' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Enable AI Assistant', 'seoplugin' ); ?></th>
                    <td>
                        <input type="checkbox" name="seoplugin_ai_enabled" value="1" 
                               <?php checked( get_option( 'seoplugin_ai_enabled', false ) ); ?> />
                        <p class="description"><?php _e( 'Enable AI-powered SEO suggestions and content analysis', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Google Gemini API Key', 'seoplugin' ); ?></th>
                    <td>
                        <input type="password" name="seoplugin_gemini_api_key" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_gemini_api_key' ) ); ?>" />
                        <p class="description">
                            <?php _e( 'API key for Google Gemini AI service', 'seoplugin' ); ?>
                            <br><a href="https://makersuite.google.com/app/apikey" target="_blank"><?php _e( 'Get your API key from Google AI Studio', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    // Render advanced settings
    private function render_advanced_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'seoplugin_advanced' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'XML Sitemap', 'seoplugin' ); ?></th>
                    <td>
                        <input type="checkbox" name="seoplugin_xml_sitemap_enabled" value="1" 
                               <?php checked( get_option( 'seoplugin_xml_sitemap_enabled', true ) ); ?> />
                        <label><?php _e( 'Enable XML sitemap generation', 'seoplugin' ); ?></label>
                        <p class="description">
                            <?php _e( 'Automatically generate XML sitemap for search engines', 'seoplugin' ); ?>
                            <br><?php _e( 'Sitemap URL:', 'seoplugin' ); ?> <a href="<?php echo home_url( '/sitemap.xml' ); ?>" target="_blank"><?php echo home_url( '/sitemap.xml' ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Breadcrumbs', 'seoplugin' ); ?></th>
                    <td>
                        <input type="checkbox" name="seoplugin_breadcrumbs_enabled" value="1" 
                               <?php checked( get_option( 'seoplugin_breadcrumbs_enabled', true ) ); ?> />
                        <label><?php _e( 'Enable breadcrumb navigation', 'seoplugin' ); ?></label>
                        <p class="description">
                            <?php _e( 'Use shortcode [seoplugin_breadcrumbs] to display breadcrumbs', 'seoplugin' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Noindex Settings', 'seoplugin' ); ?></th>
                    <td>
                        <input type="checkbox" name="seoplugin_noindex_categories" value="1" 
                               <?php checked( get_option( 'seoplugin_noindex_categories', false ) ); ?> />
                        <label><?php _e( 'Noindex category pages', 'seoplugin' ); ?></label><br>
                        
                        <input type="checkbox" name="seoplugin_noindex_tags" value="1" 
                               <?php checked( get_option( 'seoplugin_noindex_tags', false ) ); ?> />
                        <label><?php _e( 'Noindex tag pages', 'seoplugin' ); ?></label><br>
                        
                        <input type="checkbox" name="seoplugin_noindex_archives" value="1" 
                               <?php checked( get_option( 'seoplugin_noindex_archives', false ) ); ?> />
                        <label><?php _e( 'Noindex date and author archives', 'seoplugin' ); ?></label>
                        
                        <p class="description"><?php _e( 'Prevent search engines from indexing these page types', 'seoplugin' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'URL Structure', 'seoplugin' ); ?></th>
                    <td>
                        <input type="checkbox" name="seoplugin_remove_category_base" value="1" 
                               <?php checked( get_option( 'seoplugin_remove_category_base', false ) ); ?> />
                        <label><?php _e( 'Remove /category/ from category URLs', 'seoplugin' ); ?></label>
                        <p class="description"><?php _e( 'Makes category URLs cleaner (requires permalink flush)', 'seoplugin' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    // Render webmaster settings
    private function render_webmaster_settings() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'seoplugin_webmaster' ); ?>
            <table class="form-table">
                <h3><?php _e( 'Search Engine Verification', 'seoplugin' ); ?></h3>
                <tr>
                    <th scope="row"><?php _e( 'Google Search Console', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_google_verification" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_google_verification' ) ); ?>" />
                        <p class="description">
                            <?php _e( 'Google verification meta tag content', 'seoplugin' ); ?>
                            <br><a href="https://search.google.com/search-console" target="_blank"><?php _e( 'Get verification code', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Bing Webmaster Tools', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_bing_verification" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_bing_verification' ) ); ?>" />
                        <p class="description">
                            <?php _e( 'Bing verification meta tag content', 'seoplugin' ); ?>
                            <br><a href="https://www.bing.com/webmasters" target="_blank"><?php _e( 'Get verification code', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Yandex Webmaster', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_yandex_verification" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_yandex_verification' ) ); ?>" />
                        <p class="description">
                            <?php _e( 'Yandex verification meta tag content', 'seoplugin' ); ?>
                            <br><a href="https://webmaster.yandex.com/" target="_blank"><?php _e( 'Get verification code', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Pinterest', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_pinterest_verification" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_pinterest_verification' ) ); ?>" />
                        <p class="description">
                            <?php _e( 'Pinterest verification meta tag content', 'seoplugin' ); ?>
                            <br><a href="https://analytics.pinterest.com/" target="_blank"><?php _e( 'Get verification code', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
                
                <h3><?php _e( 'Analytics & Tracking', 'seoplugin' ); ?></h3>
                <tr>
                    <th scope="row"><?php _e( 'Google Analytics', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_google_analytics" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_google_analytics' ) ); ?>" 
                               placeholder="G-XXXXXXXXXX or UA-XXXXXXXX-X" />
                        <p class="description">
                            <?php _e( 'Google Analytics tracking ID (GA4 or Universal Analytics)', 'seoplugin' ); ?>
                            <br><a href="https://analytics.google.com/" target="_blank"><?php _e( 'Get tracking ID', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Google Tag Manager', 'seoplugin' ); ?></th>
                    <td>
                        <input type="text" name="seoplugin_google_tag_manager" class="regular-text" 
                               value="<?php echo esc_attr( get_option( 'seoplugin_google_tag_manager' ) ); ?>" 
                               placeholder="GTM-XXXXXXX" />
                        <p class="description">
                            <?php _e( 'Google Tag Manager container ID (takes priority over Google Analytics)', 'seoplugin' ); ?>
                            <br><a href="https://tagmanager.google.com/" target="_blank"><?php _e( 'Get container ID', 'seoplugin' ); ?></a>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    // AJAX handlers
    public function ajax_regen_og_custom() {
        check_ajax_referer( 'seoplugin_nonce', 'nonce' );
        
        $attachment_id = intval( $_POST['attachment_id'] );
        if ( ! $attachment_id ) {
            wp_send_json_error( 'Invalid attachment ID' );
        }
        
        $image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
        if ( ! $image_url ) {
            wp_send_json_error( 'Could not get image URL' );
        }
        
        wp_send_json_success( [ 'url' => $image_url ] );
    }

    public function ajax_ai_generate_title() {
        check_ajax_referer( 'seoplugin_nonce', 'nonce' );
        
        if ( ! get_option( 'seoplugin_ai_enabled', false ) ) {
            wp_send_json_error( 'AI Assistant is disabled' );
        }
        
        $api_key = get_option( 'seoplugin_gemini_api_key' );
        if ( ! $api_key ) {
            wp_send_json_error( 'Google Gemini API key not configured' );
        }
        
        $post_id = intval( $_POST['post_id'] );
        $post = get_post( $post_id );
        
        if ( ! $post ) {
            wp_send_json_error( 'Post not found' );
        }
        
        $content = wp_strip_all_tags( $post->post_content );
        $content = wp_trim_words( $content, 100 );
        
        $prompt = "Generate 5 SEO-optimized titles for this content. Each title should be 30-65 characters long and engaging:\n\n" . $content;
        
        $response = $this->call_gemini_api( $api_key, $prompt );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        
        wp_send_json_success( [ 'titles' => $response ] );
    }

    public function ajax_ai_generate_description() {
        check_ajax_referer( 'seoplugin_nonce', 'nonce' );
        
        if ( ! get_option( 'seoplugin_ai_enabled', false ) ) {
            wp_send_json_error( 'AI Assistant is disabled' );
        }
        
        $api_key = get_option( 'seoplugin_gemini_api_key' );
        if ( ! $api_key ) {
            wp_send_json_error( 'Google Gemini API key not configured' );
        }
        
        $post_id = intval( $_POST['post_id'] );
        $post = get_post( $post_id );
        
        if ( ! $post ) {
            wp_send_json_error( 'Post not found' );
        }
        
        $content = wp_strip_all_tags( $post->post_content );
        $content = wp_trim_words( $content, 150 );
        
        $prompt = "Generate 3 SEO-optimized meta descriptions for this content. Each description should be 120-160 characters long and compelling:\n\n" . $content;
        
        $response = $this->call_gemini_api( $api_key, $prompt );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        
        wp_send_json_success( [ 'descriptions' => $response ] );
    }

    public function ajax_ai_suggest_keywords() {
        check_ajax_referer( 'seoplugin_nonce', 'nonce' );
        
        if ( ! get_option( 'seoplugin_ai_enabled', false ) ) {
            wp_send_json_error( 'AI Assistant is disabled' );
        }
        
        $api_key = get_option( 'seoplugin_gemini_api_key' );
        if ( ! $api_key ) {
            wp_send_json_error( 'Google Gemini API key not configured' );
        }
        
        $post_id = intval( $_POST['post_id'] );
        $post = get_post( $post_id );
        
        if ( ! $post ) {
            wp_send_json_error( 'Post not found' );
        }
        
        $content = wp_strip_all_tags( $post->post_content );
        $content = wp_trim_words( $content, 200 );
        
        $prompt = "Analyze this content and suggest 10 relevant SEO keywords and phrases. Return only the keywords separated by commas:\n\n" . $content;
        
        $response = $this->call_gemini_api( $api_key, $prompt );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        
        wp_send_json_success( [ 'keywords' => $response ] );
    }

    public function ajax_ai_analyze_content() {
        check_ajax_referer( 'seoplugin_nonce', 'nonce' );
        
        if ( ! get_option( 'seoplugin_ai_enabled', false ) ) {
            wp_send_json_error( 'AI Assistant is disabled' );
        }
        
        $api_key = get_option( 'seoplugin_gemini_api_key' );
        if ( ! $api_key ) {
            wp_send_json_error( 'Google Gemini API key not configured' );
        }
        
        $post_id = intval( $_POST['post_id'] );
        $post = get_post( $post_id );
        
        if ( ! $post ) {
            wp_send_json_error( 'Post not found' );
        }
        
        $content = wp_strip_all_tags( $post->post_content );
        $title = get_the_title( $post_id );
        
        $prompt = "Analyze this content for SEO and provide specific recommendations for improvement. Focus on content structure, keyword usage, readability, and SEO best practices:\n\nTitle: " . $title . "\n\nContent: " . wp_trim_words( $content, 300 );
        
        $response = $this->call_gemini_api( $api_key, $prompt );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        
        wp_send_json_success( [ 'analysis' => $response ] );
    }

    private function call_gemini_api( $api_key, $prompt ) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $api_key;
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        [ 'text' => $prompt ]
                    ]
                ]
            ]
        ];
        
        $response = wp_remote_post( $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode( $body ),
            'timeout' => 30
        ] );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return new WP_Error( 'api_error', 'Invalid API response' );
        }
        
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
}

new SEOPlugin_Admin();