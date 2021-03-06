<?php
/*
 * This file is part of the statcache package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class StatCache {
    /**
     * @var StatCache|null
     */
    public static $instance = null;

    public static function getInstance(modX &$modx, array $options = array()) {
        if (null === self::$instance) {
            self::$instance = new StatCache($modx, $options);
        } else {
            self::$instance->setOptions($options);
        }
        return self::$instance;
    }

    /** @var modX $modx */
    protected $modx;
    /** @var array $options */
    protected $options;

    public function setOptions(array $options) {
        $this->options = array_merge_recursive($this->options, $options);
    }

    public function getOption($key, $default = null) {
        if (is_scalar($key) && $key !== '' && array_key_exists($key, $this->options)) {
            return $this->options[$key];
        } else {
            return $default;
        }
    }

    public function getStaticPath($resource, array $options = array()) {
        if (!$resource instanceof modResource) {
            return false;
        }
        
        if ($resource->Context->config === null) $resource->Context->prepare();
        $path = $resource->Context->getOption('statcache_path', MODX_BASE_PATH . 'statcache', $options);

        /* generate an absolute URI representation of the Resource to append to the path */
        if ($resource->get('id') === (integer)$resource->Context->getOption('site_start', 1, $options)) {
            $uri  = $resource->Context->getOption('base_url', MODX_BASE_URL, $options);
            /* use ~index.html to represent the site_start Resource */
            $uri .= '~index.html';
        } else {
            $uri = $this->modx->makeUrl($resource->get('id'), $resource->get('context_key'), '', 'abs');
            if (strpos($uri, $resource->Context->getOption('url_scheme') . $resource->Context->getOption('http_host')) === 0) {
                /* remove url_scheme and http_host from any full URLs generated by MODX automatically */
                $uri = substr($uri, strlen($resource->Context->getOption('url_scheme') . $resource->Context->getOption('http_host')));
            }
            if (substr($uri, strlen($uri) - 1) === '/' && $resource->ContentType->get('mime_type') == 'text/html') {
                /* if Resource is HTML and ends with a /, use ~index.html for the filename */
                $uri .= '~index.html';
            }
        }

        if ($this->getOption('use_url_scheme', false)) {
            $path .= '/' . str_replace('://', '', $resource->Context->getOption('url_scheme'));
        }
        if ($this->getOption('use_http_host', false)) {
            $path = rtrim($path, '/') . '/' . $resource->Context->getOption('http_host');
        }

        return $path . '/' . ltrim($uri, '/');
    }

    private function __construct(modX $modx, array $options = array()) {
        $this->modx = &$modx;
        $this->options = $options;
    }
}
