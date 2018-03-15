<?php

namespace Backpack\CRUD\ModelTraits\DimsavTranslatable;

use Dimsav\Translatable\Translatable as OriginalHasTranslations;

trait HasTranslations
{
    use OriginalHasTranslations;

    /**
     * @var bool
     */
    public $locale = false;

    /*
    |--------------------------------------------------------------------------
    |                 DIMSAV/LARAVEL-TRANSLATABLE OVERWRITES
    |--------------------------------------------------------------------------
    */

    public function isTranslation($key){
        return $this->isTranslationAttribute($key);
    }
    
    public function getAttributeValue($key)
    {
        if (! $this->isTranslation($key)) {
            return parent::getAttributeValue($key);
        }

        $translation = $this->getAttribute($key);

        // if it's a fake field, json_encode it
        if (is_array($translation)) {
            return json_encode($translation);
        }

        return $translation;
    }
    
    /*public function getAttributes(){
        return $this->translatedAttributes;
    }*/
    
    public function orderTranslationBy($query, $field, $order){
        $query = $this->addTranslationJoin($query);
        return $query->orderBy('t.' . $field, $order);
    }
    
    public function addTranslationJoin($query){
        $table = $this->getTable();
        $ttable = $this->getTranslationsTable();
        if ($query->getQuery()->joins) {
            foreach($query->getQuery()->joins as $JoinClause) {
                if($JoinClause->table == $ttable.' as t') {
                    return $query;
                }
            }
        }
        return $query->leftJoin($ttable . ' as t', function ($join) use ($table) {
            $join->on($table . '.id', '=', 't.'.$table.'_id')
                ->where('t.locale', '=', $this->getLocale());
        })
        ->select($table.'.*')
        ->groupBy($table . '.id')
        ->with('translations');
    }

    /*
    |--------------------------------------------------------------------------
    |                            ELOQUENT OVERWRITES
    |--------------------------------------------------------------------------
    */

    /**
     * Create translated items as json.
     *
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $attributes = array_except($attributes, ['locale']);
        $model = new static();

        // do the actual saving
        foreach ($attributes as $attribute => $value) {
            $model->setAttribute($attribute, $value);
        }
        $model->save();

        return $model;
    }

    /**
     * Update translated items as json.
     *
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (! $this->exists) {
            return false;
        }

        $attributes = array_except($attributes, ['locale']);
        
        // do the actual saving
        foreach ($attributes as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        return $this->save($options);
    }

    /*
    |--------------------------------------------------------------------------
    |                            CUSTOM METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a model is translatable, by the adapter's standards.
     *
     * @return bool
     */
    public function translationEnabledForModel()
    {
        return property_exists($this, 'translatedAttributes');
    }

    /**
     * Get all locales the admin is allowed to use.
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        $locales = $this->getLocales();
        $locales_ok = [];
        foreach ($locales as $k => $l) {
            $locales_ok[$l] = $l;
        }
        return $locales_ok;
    }

    /**
     * Set the locale property. Used in normalizeLocale() to force the translation
     * to a different language that the one set in app()->getLocale();.
     *
     * @param string
     */
    public function setLocale($locale)
    {
        $this->setDefaultLocale($locale);
    }

    /**
     * Get the locale property. Used in SpatieTranslatableSluggableService
     * to save the slug for the appropriate language.
     *
     * @param string
     */
    public function getLocale()
    {
        return $this->locale();
    }

    /**
     * Magic method to get the db entries already translated in the wanted locale.
     *
     * @param string $method
     * @param array $parameters
     * @return
     */
    public function __call($method, $parameters)
    {
        switch ($method) {
            // translate all find methods
            case 'find':
            case 'findOrFail':
            case 'findMany':
            case 'findBySlug':
            case 'findBySlugOrFail':

                $translation_locale = \Request::input('locale', \App::getLocale());

                if ($translation_locale) {
                    $item = parent::__call($method, $parameters);

                    if ($item) {
                        $item->setLocale($translation_locale);
                    }

                    return $item;
                }

                return parent::__call($method, $parameters);
                break;

            // do not translate any other methods
            default:
                return parent::__call($method, $parameters);
                break;
        }
    }
}
