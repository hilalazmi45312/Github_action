<?php
namespace FileBird\PageBuilders\Elementor;

use FileBird\Classes\Tree;
use FileBird\Classes\Helpers;

defined('ABSPATH') || exit;

/**
 * FileBird Gallery Widget for Elementor
 * 
 * Creates a widget that displays images from a FileBird folder
 * 
 * @since 1.0.0
 */
class FileBirdGalleryWidget extends \Elementor\Widget_Base {
    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'filebird_gallery';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__('FileBird Gallery', 'filebird');
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories() {
        return ['filebird'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $folders = Tree::getFolders('ord', 'ASC');
        
        // Initialize options array
        $folder_options = [
            '0' => esc_html__('Select Folder', 'filebird'),
        ];
        
        // Build options including all children with ordered keys
        $this->build_folder_options($folders, $folder_options);

        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Gallery Settings', 'filebird'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'folder_id',
            [
                'label' => esc_html__('Select Folder', 'filebird'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $folder_options,
                'default' => '0',
                'description' => esc_html__('Choose a folder to display images from', 'filebird'),
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'filebird'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'default' => '3',
                'description' => esc_html__('Number of columns to display', 'filebird'),
                'selectors' => [
                    '{{WRAPPER}} .filebird-gallery-item' => 'width: calc(100% / {{VALUE}});',
                ],
            ]
        );

        $this->add_control(
            'link_to',
            [
                'label' => esc_html__('Link To', 'filebird'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'none' => esc_html__('None', 'filebird'),
                    'file' => esc_html__('Media File', 'filebird'),
                    'post' => esc_html__('Attachment Page', 'filebird'),
                ],
                'default' => 'file',
                'description' => esc_html__('Link behavior when clicking on image', 'filebird'),
            ]
        );

        $this->add_control(
            'size',
            [
                'label' => esc_html__('Image Size', 'filebird'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array_merge(['full' => esc_html__('Full', 'filebird')], $this->get_image_sizes()),
                'default' => 'medium',
                'description' => esc_html__('Select image size', 'filebird'),
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => esc_html__('Order By', 'filebird'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'date' => esc_html__('Date', 'filebird'),
                    'title' => esc_html__('Title', 'filebird'),
                    'rand' => esc_html__('Random', 'filebird'),
                ],
                'default' => 'date',
                'description' => esc_html__('Order images by', 'filebird'),
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => esc_html__('Order', 'filebird'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'DESC' => esc_html__('Descending', 'filebird'),
                    'ASC' => esc_html__('Ascending', 'filebird'),
                ],
                'default' => 'DESC',
                'description' => esc_html__('Sort order', 'filebird'),
            ]
        );

        $this->end_controls_section();

        // Style section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Gallery Style', 'filebird'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'image_spacing',
            [
                'label' => esc_html__('Spacing', 'filebird'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 5,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .filebird-gallery' => 'margin: -{{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .filebird-gallery-item' => 'padding: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'selector' => '{{WRAPPER}} .filebird-gallery-item img',
            ]
        );

        $this->add_control(
            'image_border_radius',
            [
                'label' => esc_html__('Border Radius', 'filebird'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .filebird-gallery-item img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * For older versions of Elementor
     */
    protected function _register_controls() {
        // For backwards compatibility
        $this->register_controls();
    }

    /**
     * Recursively builds folder options including all children
     * 
     * @param array $folders Array of folders
     * @param array &$options Reference to options array to be filled
     * @param string $prefix Optional prefix for child folders
     */
    private function build_folder_options($folders, &$options, $prefix = '') {
        if (!is_array($folders)) {
            return;
        }
        
        foreach ($folders as $folder) {
            $options['id_' . $folder['id']] = $prefix . $folder['text'];
            
            // Process children recursively if they exist
            if (!empty($folder['children']) && is_array($folder['children'])) {
                $this->build_folder_options($folder['children'], $options, $prefix . '— ');
            }
        }
    }

    /**
     * Get available image sizes
     * 
     * @return array
     */
    private function get_image_sizes() {
        $sizes = [];
        $wp_sizes = get_intermediate_image_sizes();
        
        foreach ($wp_sizes as $size) {
            $sizes[$size] = $size;
        }
        
        return $sizes;
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $folder_id = $settings['folder_id'] ?? 0;
        if( $folder_id !== 0 ) {
            $folder_id = (int)str_replace('id_', '', $folder_id);

        if ($folder_id <= 0) {
            echo '<div class="filebird-gallery-error">' . esc_html__('Please select a folder', 'filebird') . '</div>';
            return;
        }

        $attachment_ids = Helpers::getAttachmentIdsByFolderId($folder_id);
        
        if (empty($attachment_ids)) {
            echo '<div class="filebird-gallery-empty">' . esc_html__('No images found in this folder', 'filebird') . '</div>';
            return;
        }

        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post__in' => $attachment_ids,
            'posts_per_page' => -1,
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
        ];

        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            echo '<div class="filebird-gallery-empty">' . esc_html__('No images found in this folder', 'filebird') . '</div>';
            wp_reset_postdata();
            return;
        }
        
        ?>
        <div class="filebird-gallery filebird-gallery-columns-<?php echo esc_attr($settings['columns']); ?>">
            <?php
            while ($query->have_posts()) {
                $query->the_post();
                $attachment_id = get_the_ID();
                $image_src = wp_get_attachment_image_src($attachment_id, $settings['size']);
                $image_full = wp_get_attachment_image_src($attachment_id, 'full');
                
                $link = '';
                switch ($settings['link_to']) {
                    case 'file':
                        $link = $image_full[0];
                        break;
                    case 'post':
                        $link = get_attachment_link($attachment_id);
                        break;
                }
                ?>
                <div class="filebird-gallery-item">
                    <?php if ($link) : ?>
                        <a href="<?php echo esc_url($link); ?>">
                    <?php endif; ?>
                    
                    <img src="<?php echo esc_url($image_src[0]); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" />
                    
                    <?php if ($link) : ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        
        wp_reset_postdata();
        }
    }
}