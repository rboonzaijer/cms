<?php

namespace Statamic\Imaging;

use Exception;
use League\Glide\Urls\UrlBuilderFactory;
use Statamic\Contracts\Assets\Asset;
use Statamic\Facades\URL;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class GlideUrlBuilder extends ImageUrlBuilder
{
    /**
     * @var array
     */
    protected $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Build the URL.
     *
     * @param  \Statamic\Contracts\Assets\Asset|string  $item
     * @param  array  $params
     * @param  string|null  $filename
     * @return string
     *
     * @throws \Exception
     */
    public function build($item, $params, $filename = null)
    {
        $this->item = $item;

        switch ($this->itemType()) {
            case 'url':
                $path = 'http/'.base64_encode($item);

                if (! $filename) {
                    $filename = $this->optionallySetFilename(Str::afterLast($item, '/'), $params);
                }

                break;
            case 'asset':
                $path = 'asset/'.base64_encode($this->item->containerId().'/'.$this->item->path());

                if (! $filename) {
                    $filename = $this->optionallySetFilename(Str::afterLast($this->item->path(), '/'), $params);
                }

                break;
            case 'id':
                $path = 'asset/'.base64_encode(str_replace('::', '/', $this->item));
                break;
            case 'path':
                $path = URL::encode($this->item);
                break;
            default:
                throw new Exception('Cannot build a Glide URL without a URL, path, or asset.');
        }

        $builder = UrlBuilderFactory::create($this->options['route'], $this->options['key']);

        if ($filename) {
            $path .= Str::ensureLeft(URL::encode($filename), '/');
        }

        if (isset($params['mark']) && $params['mark'] instanceof Asset) {
            $asset = $params['mark'];
            $params['mark'] = 'asset::'.base64_encode($asset->containerId().'/'.$asset->path());
        }

        return URL::prependSiteRoot($builder->getUrl($path, $params));
    }

    /**
     * Should the filename (and optional parameters) be set based on the config setting
     *
     * @return bool|string
     */
    private function optionallySetFilename(string $filename, array $params = [])
    {
        if (! config('statamic.assets.image_manipulation.append_original_filename', false) &&
            ! config('statamic.assets.image_manipulation.prepend_used_parameters', false)) {
            return false;
        }

        $parts = [];

        if (config('statamic.assets.image_manipulation.prepend_used_parameters', false)) {
            $flatParams = Arr::join(Arr::map(Arr::dot($params), function (string $value, string $key) {
                return $key.$value;
            }), '-');
            $parts[] = Str::slug($flatParams);
        }

        if (config('statamic.assets.image_manipulation.append_original_filename', false)) {
            $parts[] = $filename;
        }

        return Arr::join($parts, '-');
    }
}
