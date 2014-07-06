<?php
/*
Plugin Name: Easy Sitemap
Plugin URI: https://github.com/mingshun/easy-sitemap
Description: Generate the sitemap for WordPress.
Author: mingshun
Author URI: https://github.com/mingshun
Version: 1.0
*/

class EasySitemap {
  function activate() {
    $this->flush_rewrite_rules();
  }

  function deactive() {
    $this->flush_rewrite_rules();
  }

  function create_rewrite_rules($rules) {
    $my_rules = array('^sitemap.xml$' => 'index.php?sitemap=1');
    $rules = $my_rules + $rules;
    return $rules;
  }

  function add_query_vars($vars) {
    $vars[] = 'sitemap';
    return $vars;
  }

  function add_sitemap_link() {
    $sitemap_link = get_bloginfo('url') . '/sitemap.xml';
    echo '<link ref="sitemap" href="' . $sitemap_link . '" />' . "\n";
  }

  function flush_rewrite_rules() {
    flush_rewrite_rules(false);
  }

  function template_redirect_intercept() {
    global $wp_query;
    if ($wp_query->get('sitemap')) {
      $this->output_sitemap();
      exit;
    }
  }

  function generate_url_items($dom, $urlset, $post_type, $post_changefreq, $post_priority) {
    $timezone = new DateTimeZone('Asia/Hong_Kong');
    $posts = get_posts(array(
      'numberposts' => -1,
      'orderby' => 'post_date',
      'order' => 'DESC',
      'post_type' => $post_type,
      'post_status' => 'publish',
    ));
    foreach ($posts as $post) {
      $url = $urlset->appendChild($dom->createElement('url'));

      $locNode = $url->appendChild($dom->createElement('loc'));
      $locNode->appendChild($dom->createTextNode(get_permalink($post->ID)));

      $lastmod = $url->appendChild($dom->createElement('lastmod'));
      $date = DateTime::createFromFormat('Y-m-d H:i:s', $post->post_modified_gmt);
      $date->setTimezone($timezone);
      $lastmod->appendChild($dom->createTextNode($date->format(DateTime::W3C)));

      $changefreq = $url->appendChild($dom->createElement('changefreq'));
      $changefreq->appendChild($dom->createTextNode($post_changefreq));

      $priority = $url->appendChild($dom->createElement('priority'));
      $priority->appendChild($dom->createTextNode($post_priority));
    }
  }

  function generate_sitemap() {
    $dom = new DomDocument('1.0', 'UTF-8');

    $urlset = $dom->createElementNS('http://www.google.com/schemas/sitemap/0.84', 'urlset');
    $dom->appendChild($urlset);
    $urlset->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $urlset->setAttributeNS(
      'http://www.w3.org/2001/XMLSchema-instance',
      'xsi:schemaLocation',
      'http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd');

    $this->generate_url_items($dom, $urlset, 'post', 'weekly', '0.8');
    $this->generate_url_items($dom, $urlset, 'page', 'monthly', '0.5');

    $dom->formatOutput = true;
    $sitemap = $dom->saveXML();

    return $sitemap;
  }

  function output_sitemap() {
    header('Content-Type: text/xml; Charset=UTF-8');
    echo $this->generate_sitemap();
  }
}

$easySitemap = new EasySitemap();
register_activation_hook(__file__, array($easySitemap, 'activate'));
register_deactivation_hook(__FILE__, array($easySitemap, 'deactive'));
add_filter('rewrite_rules_array', array($easySitemap, 'create_rewrite_rules'));
add_filter('query_vars',array($easySitemap, 'add_query_vars'));
add_filter('wp_head', array($easySitemap, 'add_sitemap_link'));
add_filter('admin_init', array($easySitemap, 'flush_rewrite_rules'));
add_action('template_redirect', array($easySitemap, 'template_redirect_intercept'));
?>
