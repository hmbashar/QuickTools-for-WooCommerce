<?php
namespace QTFW\Includes\Admin\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class Total_Sold {

    /**
     * The constructor method.
     *
     * Adds hooks to integrate the feature to the codebase.
     *
     * Adds a filter to add a column to the product table, makes the column sortable,
     * populates the column with data, and adds custom sorting for the column.
     */
    public function __construct() {
        // Add hooks to integrate the feature
        add_filter('manage_edit-product_columns', [$this, 'add_sold_column_to_product_table']);
        add_filter('manage_edit-product_sortable_columns', [$this, 'make_sold_column_sortable_in_product_table']);
        add_action('manage_product_posts_custom_column', [$this, 'populate_sold_column_in_product_table'], 10, 2);
        add_action('pre_get_posts', [$this, 'custom_sorting_for_sold_column']);
    }

    // Add Sold column to the Products table
    public function add_sold_column_to_product_table($columns) {
        // Create a new array for reordered columns
        $new_columns = [];
    
        // Loop through existing columns and insert the Sold column after the Product Name column
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
    
            // Insert Sold column after the 'name' (Product Name) column
            if ($key === 'name') {
                $new_columns['product_sold'] = __('Sold', 'quicktools-for-woocommerce');
            }
        }
    
        return $new_columns;
    }
    
    
    // Make the Sold column sortable
    public function make_sold_column_sortable_in_product_table($columns) {
        $columns['product_sold'] = 'product_sold';
        return $columns;
    }

    // Populate the Sold column with the number of units sold
    public function populate_sold_column_in_product_table($column, $post_id) {
        if ($column === 'product_sold') {
            $product = wc_get_product($post_id);
            if ($product->is_type('simple')) {
                $units_sold = get_post_meta($post_id, 'total_sales', true);
                echo esc_html($units_sold ? $units_sold : '0');  // Output the sold number or 0
            } else {
                echo '-';
            }
        }
    }

    // Define custom sorting for the Sold column
    public function custom_sorting_for_sold_column($query) {
        global $pagenow;
        $orderby = $query->get('orderby');
        if ($pagenow === 'edit.php' && $orderby === 'product_sold') {
            $query->set('meta_key', 'total_sales');
            $query->set('orderby', 'meta_value_num');
        }
    }
}
