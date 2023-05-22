<?php

use TenWebOptimizer\OptimizerUtils;

$two_critical_status = $TwoSettings->get_settings("two_critical_status");
$two_critical_font_status = $TwoSettings->get_settings("two_critical_font_status");
$two_use_font_ready = $TwoSettings->get_settings("two_use_font_ready");
$two_critical_pages = OptimizerUtils::getCriticalPages();
$two_critical_sizes = $TwoSettings->get_settings("two_critical_sizes");
$two_critical_url_args = $TwoSettings->get_settings("two_critical_url_args");
$two_critical_blocked = get_option("two_critical_blocked");
$two_critical_blocked_class = "two_critical_blocked";
$critical_flag = \TenWebWpTransients\OptimizerTransients::get("two_critical_flag");
$no_optimize_pages_list = get_option('no_optimize_pages');

if($two_critical_blocked){
    $two_critical_blocked_class = "";
}


$two_uncritical_load_types = array(
        "async"=>"Async",
        "on_interaction"=>"On interaction",
        "not_load"=>"Do not load",
);
$two_critical_wait_until = array(
        "load"=>"load",
        "domcontentloaded"=>"domcontentloaded",
        "networkidle0"=>"networkidle0",
        "networkidle2"=>"networkidle2",
);
if(!is_array($two_critical_pages)){
  $two_critical_pages = array();
}
if(!is_array($two_critical_sizes)){
  $two_critical_sizes = array();
}
$critical_checked = "";
$hide_critical = "style='display:none;'";
if($two_critical_status === "true"){
    $critical_checked = "checked";
    $hide_critical = "";
}
$two_use_font_ready_check = ($two_use_font_ready == 'on') ? "checked" : "";
$critical_font_checked = ($two_critical_font_status == "true") ? "checked" : "";

?>
<div class="two_settings_tab two_tab_critical_css">
    <?php if((int)$critical_flag > 0):?>
        <?php echo "Count of CSS Generation function calls(API might not be called): " . esc_html( $critical_flag ); ?>
    <?php endif;?>
    <div class="two_settings_option two_critical">
        <input <?php echo esc_html( $critical_checked );?> type="checkbox" name="two_critical" id="two_critical">
        <label for="two_critical"
               class="wd-label"><?php _e('Critical css', 'tenweb-speed-optimizer'); ?></label>
        <p class="description"><?php _e('Enable this option for critical css', 'tenweb-speed-optimizer') ?></p>
    </div>
    <div class="two_settings_option two_critical_font">
        <input <?php echo esc_html( $critical_font_checked );?> type="checkbox" name="two_critical_font" id="two_critical_font">
        <label for="two_critical_font"
               class="wd-label"><?php _e('Critical fonts', 'tenweb-speed-optimizer'); ?></label>
        <p class="description"><?php _e('Enable this option for critical fonts', 'tenweb-speed-optimizer') ?></p>
    </div>
    <div id="two_critical_options" <?php echo esc_html( $hide_critical ); ?>>
        <div class="two_settings_option two_use_font_ready">
            <input <?php echo esc_html( $two_use_font_ready_check );?> type="checkbox" name="two_use_font_ready" id="two_use_font_ready">
            <label for="two_use_font_ready"
                   class="wd-label"><?php _e('Load not critical CSS after fonts load', 'tenweb-speed-optimizer'); ?></label>
            <p class="description"><?php _e('Load not critical CSS files after critical fonts are loaded', 'tenweb-speed-optimizer') ?></p>
        </div>
        <div class="two_settings_option two_page_for_critical">
            <label for="two_page_for_critical" class="wd-label"><?php _e('Select page to generate critical CSS', 'tenweb-speed-optimizer'); ?></label>
            <select name="two_page_for_critical" id="two_page_for_critical">
            </select>
            <button class="two_add_critical_css_row button button-primary"><?php _e('ADD'); ?></button>
        </div>
        <div class="two_settings_option two_page_for_critical">
            <label class="wd-label" for="two_critical_url_args"><?php _e('URL query arguments:', 'tenweb-speed-optimizer') ?></label>
            <input type="text" id="two_critical_url_args" value="<?php echo esc_attr( $two_critical_url_args ); ?>" style="width:300px">
        </div>
        <div class="two_critical_error <?php echo esc_attr( $two_critical_blocked_class ); ?> notice notice-error">
            <h2>Critical CSS Generation is blocked</h2>
            <p>One of your services is blocking our IP, please contact our support team.</p>
        </div>
        <div class="two_critical_tables two_page_for_critical">
            <div class="two_critical_pages">
                <table id="two_critical_pages" class="display" style="width:100%">
                    <thead>
                    <tr>
                        <th>Page name</th>
                        <th>Size</th>
                        <th>Load</th>
                        <th>Wait until</th>
                        <th>Use uncritical</th>
                        <th>Update date</th>
                        <th>Generate/Delete/Clear</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php foreach($two_critical_pages as $critical_page):
                          if(!isset($critical_page["id"])){
                             continue;
                          }
                          $two_generate_icon = "dashicons-database-add";
                          if(isset($critical_page["status"]) && $critical_page["status"] == "success" && isset($critical_page["critical_css"]) &&!empty($critical_page["critical_css"])){
                            $two_generate_icon = "dashicons-database-view";
                          }
                          $critical_page_status = "not_started";
                          $critical_page_status_classes="";
                          if(isset($critical_page["status"])){
                              $critical_page_status = $critical_page["status"];
                              if($critical_page_status === "in_progress"){
                                  $critical_page_status_classes = "is-active";
                                  $two_generate_icon.=" two_disabled";
                              }
                          }
                            $use_uncritical = "";
                            if(isset($critical_page["use_uncritical"]) && $critical_page["use_uncritical"] == "true"){
                                $use_uncritical = "checked";
                            }

                            if(OptimizerUtils::is_wpml_active()){
                              $flag_url = OptimizerUtils::get_wpml_post_flag_url($critical_page["id"]);
                              $flag_img = "<img src='".$flag_url."' class='two_flag' />";
                            }else{
                              $flag_img = "";
                            }

                            ?>
                            <tr data-page_id="<?php echo esc_attr( $critical_page['id'] ); ?>"  data-status="">
                                <td data-page_url="<?php echo esc_url( $critical_page["url"] ); ?>" data-page_id="<?php echo esc_attr( $critical_page["id"] ); ?>" class="critical_page_url"><a href="<?php echo esc_url( $critical_page["url"] ); ?>" target="_blank"><?php echo wp_kses_post( $flag_img . $critical_page["title"] ); ?></a></td>
                                <td class="critical_page_sizes">
                                    <select multiple class="two_critical_sizes_select">
                                      <?php foreach($two_critical_sizes as $uid=>$size): ?>
                                          <?php
                                            $check_size = "";
                                            if(isset($critical_page["sizes"]) && is_array($critical_page["sizes"])){
                                                foreach ($critical_page["sizes"] as $selected_size){
                                                    if(isset($selected_size)){
                                                        if($size["uid"] === $selected_size || (isset($selected_size["uid"]) && $size["uid"] === $selected_size["uid"])){
                                                            $check_size = "selected";
                                                        }
                                                    }
                                                }
                                            }
                                          ?>
                                          <option <?php echo esc_html( $check_size ); ?> data-uid="<?php echo esc_attr( $uid ); ?>" data-width="<?php echo esc_attr( $size["width"] ); ?>" data-height="<?php echo esc_attr( $size["height"] ); ?>" data-media="<?php echo esc_attr( $size["media"] ); ?>"><?php echo esc_html( $size["width"] ); ?>/<?php echo esc_html( $size["height"] ); ?></option>
                                      <?php endforeach;?>
                                    </select>
                                </td>
                                <td class="critical_page_load_actions">
                                    <select>
                                        <?php foreach($two_uncritical_load_types as $key=>$val):?>
                                            <?php
                                                $two_uncritical_load_types_selected = "";
                                                if($key ===$critical_page["load_type"]){
                                                    $two_uncritical_load_types_selected = "selected";
                                                }
                                            ?>
                                            <option <?php echo esc_html( $two_uncritical_load_types_selected ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $val ); ?></option>
                                        <?php endforeach;?>
                                    </select>
                                </td>
                                <td class="critical_page_wait_until">
                                    <select>
                                        <?php foreach($two_critical_wait_until as $key=>$val):?>
                                            <?php
                                                $two_critical_wait_until_selected = "";
                                                if($key ===$critical_page["wait_until"]){
                                                    $two_critical_wait_until_selected = "selected";
                                                }
                                            ?>
                                            <option <?php echo esc_html( $two_critical_wait_until_selected ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $val ); ?></option>
                                        <?php endforeach;?>
                                    </select>
                                </td>
                                <td class="use_uncritical">
                                    <input type="checkbox" name="use_uncritical" <?php echo esc_html( $use_uncritical ); ?>>
                                </td>
                                <td>
                                    <span><?php echo (isset($critical_page["critical_date"])) ? esc_html( date("Y-m-d H:i:s", $critical_page["critical_date"]) ) : ""; ?></span>
                                </td>
                                <td>
                                    <span class="two_generate_critical dashicons <?php echo esc_attr( $two_generate_icon ); ?>"></span>
                                    <span data-page_id="<?php echo esc_attr( $critical_page["id"] ); ?>" class="two_delete_critical_page dashicons dashicons-trash"></span>
                                    <span data-page_url="<?php echo esc_url( $critical_page["url"] ); ?>" class="two_clear_cloudflare_cache dashicons dashicons-editor-removeformatting"></span>
                                    <span class="spinner <?php echo esc_attr( $critical_page_status_classes ); ?>"></span>
                                    <input type="hidden" value="<?php echo esc_attr( $critical_page_status ); ?>" class="two_critical_page_status">
                                </td>
                            </tr>
                        <?php endforeach;?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Page name</th>
                        <th>Size</th>
                        <th>Load</th>
                        <th>Wait until</th>
                        <th>Use uncritical</th>
                        <th>Update date</th>
                        <th>Generate/Delete/Clear</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="two_critical_sizes">
                <p>Sizes for critical generation</p>
                <table id="two_critical_sizes" class="display" style="width:100%">
                    <thead>
                    <tr>
                        <th>Width</th>
                        <th>Height</th>
                        <th>Css media</th>
                        <th>Delete</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($two_critical_sizes as $uid => $size): ?>
                        <tr class="<?php echo esc_attr( $uid ); ?>" data-uid="<?php echo esc_attr( $uid ); ?>">
                            <td class="critical_page_width"><?php echo esc_html( $size["width"] ); ?></td>
                            <td class="critical_page_height"><?php echo esc_html( $size["height"] ); ?></td>
                            <td class="critical_size_media"><input type="text" name="critical_size_media" class="critical_size_media_input" value="<?php echo isset($size["media"]) ? esc_attr( $size["media"] ) : ""; ?>"></td>
                            <td><span class="two_delete_critical_size dashicons dashicons-trash"></span></td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Width</th>
                        <th>Height</th>
                        <th>Css media</th>
                        <th>Delete</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="two-no-optimize-pages" style="margin-top: 40px;">
                <p class="wd-label">Pages in no optimize pages list from Dashboard </p>
                <table id="two-no-optimize-pages" class="display" style="width:100%; display: block;
                      border:1px solid black;
                      overflow: scroll;
                        height: 100px;">
                    <tbody>
                        <?php
                        if(is_array($no_optimize_pages_list)){
                            foreach ($no_optimize_pages_list as $page ) { ?>
                                <tr><td><?php echo esc_html( $page ); ?></td></tr>
                            <?php }
                        }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
