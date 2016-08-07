<?php
namespace TypeRocket\Models;

use TypeRocket\Elements\Fields\Field,
    TypeRocket\Http\Cookie;

abstract class Model
{

    protected $id = null;
    protected $fillable = [];
    protected $guard = [];
    protected $format = [];
    protected $static = [];
    protected $default = [];
    protected $errors = null;
    protected $builtin = [];
    private $data = null;
    private $old = null;

    /**
     * Construct Model based on resource
     */
    public function __construct()
    {
        $reflect = new \ReflectionClass( $this );
        $type    = substr( $reflect->getShortName(), 0, - 5 );
        $suffix  = '';

        if ( ! empty( $type )) {
            $suffix = '_' . $type;
        }

        $this->init();
        $this->fillable = apply_filters( 'tr_model_fillable' . $suffix, $this->fillable, $this );
        $this->guard    = apply_filters( 'tr_model_guard' . $suffix, $this->guard, $this );
        $this->format   = apply_filters( 'tr_model_format' . $suffix, $this->format, $this );
        do_action( 'tr_model', $this );
    }

    /**
     * Basic initialization
     *
     * Used on construction in concrete classes
     *
     * @return $this
     */
    protected function init()
    {
        return $this;
    }

    /**
     * Set Static Fields
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array $static
     *
     * @return $this
     */
    public function setStaticFields( array $static )
    {
        $this->static = $static;

        return $this;
    }

    /**
     * Set Default Fields
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array $default
     *
     * @return $this
     */
    public function setDefaultFields( array $default )
    {
        $this->static = $default;

        return $this;
    }

    /**
     * Set Fillable
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array $fillable
     *
     * @return $this
     */
    public function setFillableFields( array $fillable )
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Set Format
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array $format
     *
     * @return $this
     */
    public function setFormatFields( array $format )
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set Guard
     *
     * Fields that are write protected by default unless fillable
     *
     * @param array $guard
     *
     * @return $this
     */
    public function setGuardFields( array $guard )
    {
        $this->guard = $guard;

        return $this;
    }

    /**
     * Append Fillable
     *
     * Add a fillable field.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function appendFillableField( $field_name )
    {
        if ( ! in_array( $field_name, $this->fillable ) && ! in_array( $field_name, $this->guard ) ) {
            $this->fillable[] = $field_name;
        }

        return $this;
    }

    /**
     * Append Guard
     *
     * Add a field to guard if not set to fillable.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function appendGuardField( $field_name )
    {
        if ( ! in_array( $field_name, $this->fillable ) && ! in_array( $field_name, $this->guard ) ) {
            $this->guard[] = $field_name;
        }

        return $this;
    }

    /**
     * Append Format
     *
     * Add a field to format.
     *
     * @param string $field_name dot notation with support for wild card *
     * @param callable $callback function or method to call on $field_name
     *
     * @return $this
     */
    public function appendFormatField( $field_name, $callback )
    {
        if ( ! array_key_exists( $field_name, $this->format )) {
            $this->format[$field_name] = $callback;
        }

        return $this;
    }

    /**
     * Remove Guard
     *
     * Remove field from guard.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function removeGuardField( $field_name )
    {
        if ( in_array( $field_name, $this->guard ) ) {
            unset($this->guard[array_search($field_name, $this->guard)]);
        }

        return $this;
    }

    /**
     * Remove Fillable
     *
     * Remove field from fillable.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function removeFillableField( $field_name )
    {
        if ( in_array( $field_name, $this->fillable ) ) {
            unset($this->fillable[array_search($field_name, $this->fillable)]);
        }

        return $this;
    }

    /**
     * Remove Format
     *
     * Remove field from format.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function removeFormatField( $field_name )
    {
        if ( in_array( $field_name, $this->format ) ) {
            unset($this->format[array_search($field_name, $this->format)]);
        }

        return $this;
    }

    /**
     * Unlock Field
     *
     * Unlock field by adding it to fillable and removing it from guard.
     *
     * @param $field_name
     *
     * @return $this
     */
    public function unlockField( $field_name )
    {
        if ( in_array( $field_name, $this->guard ) ) {
            unset($this->guard[array_search($field_name, $this->guard)]);
        }

        if ( !empty($this->fillable) && ! in_array( $field_name, $this->fillable ) && ! in_array( $field_name, $this->guard ) ) {
            $this->fillable[] = $field_name;
        }

        return $this;
    }

    /**
     * Resource ID
     *
     * The ID of the resource being used.
     *
     * @return null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get Errors
     *
     * Get any errors that have been logged
     *
     * @return null
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get Fillable Fields
     *
     * Get all the fields that can be filled
     *
     * @return array|mixed|void
     */
    public function getFillableFields()
    {
        return $this->fillable;
    }

    /**
     * Get Guard Fields
     *
     * Get all the fields that have been write protected
     *
     * @return array|mixed|void
     */
    public function getGuardFields()
    {
        return $this->guard;
    }

    /**
     * Get Builtin Fields
     *
     * Get all the fields that are not saved as meta fields
     *
     * @return array
     */
    public function getBuiltinFields()
    {
        return $this->builtin;
    }

    /**
     * Get Data by key
     *
     * @param $key
     *
     * @return null
     */
    public function getData( $key )
    {
        $data = null;

        if (array_key_exists( $key, $this->data )) {
            $data = $this->data[$key];
        }

        return $data;
    }

    /**
     * Set Data by key
     *
     * @param $key
     * @param $value
     *
     * @return $this
     */
    protected function setData( $key, $value )
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get only the fields that are considered to
     * be meta fields.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function getFilteredMetaFields( array $fields )
    {
        $builtin = array_flip( $this->builtin );

        return array_diff_key( $fields, $builtin );
    }

    /**
     * Get only the fields that are considered to
     * be builtin fields.
     *
     * @param array $fields
     *
     * @return array
     */
    protected function getFilteredBuiltinFields( array $fields )
    {
        $builtin = array_flip( $this->builtin );

        return array_intersect_key( $fields, $builtin );
    }

    /**
     * Get fields that have been checked against fillable and guard.
     * Fillable fields override guarded fields.
     *
     * @param array $fields
     *
     * @return mixed|void
     */
    public function secureFields( array $fields )
    {
        // Fillable
        $fillable = [];
        if ( ! empty( $this->fillable ) && is_array( $this->fillable )) {
            foreach ($this->fillable as $field_name) {
                if (isset( $fields[$field_name] )) {
                    $fillable[$field_name] = $fields[$field_name];
                }
            }
            $fields = $fillable;
        }

        // Guard
        if ( ! empty( $this->guard ) && is_array( $this->guard )) {
            foreach ($this->guard as $field_name) {
                if (isset( $fields[$field_name] ) && ! in_array( $field_name, $this->fillable )) {
                    unset( $fields[$field_name] );
                }
            }
        }

        // Format
        if ( ! empty( $this->format ) && is_array( $this->format )) {
            $fields = $this->formatFields($fields);
        }

        return apply_filters( 'tr_model_secure_fields', $fields, $this );
    }

    /**
     * Get value from database from typeRocket bracket syntax
     *
     * @param $field
     *
     * @return array|mixed|null|string
     */
    public function getFieldValue( $field )
    {
        if ($field instanceof Field) {
            $field = $field->getDots();
        }

        if ($this->id == null && ! $this->old ) {
            return null;
        }

        $keys = $this->getDotKeys( $field );

        if($this->old) {
            $data = $this->old[$keys[0]];
        } else {
            $data = $this->getBaseFieldValue( $keys[0] );
        }

        return $this->parseValueData( $data, $keys );
    }

    /**
     * Get old stored fields
     */
    public function oldStore() {
        if( !empty($_COOKIE['tr_old_fields']) ) {
            $cookie = new Cookie();
            $this->old = $cookie->getTransient('tr_old_fields');
        }
    }

    /**
     * Parse data by walking through keys
     *
     * @param $data
     * @param $keys
     *
     * @return array|mixed|null|string
     */
    private function parseValueData( $data, $keys )
    {
        $mainKey = $keys[0];
        if (isset( $mainKey ) && ! empty( $data )) {

            if (  is_string($data) && tr_is_json($data)  ) {
                $data = json_decode( $data, true );
            }

            if ( is_string($data) && is_serialized( $data ) ) {
                $data = unserialize( $data );
            }

            // unset first key since $data is already set to it
            unset( $keys[0] );

            if ( ! empty( $keys ) && is_array( $keys )) {
                foreach ($keys as $name) {
                    $data = ( isset( $data[$name] ) && $data[$name] !== '' ) ? $data[$name] : null;
                }
            }

        }

        return $data;
    }

    /**
     * Format fields
     *
     * @param array $fields
     *
     * @return array
     */
    private function formatFields(array $fields) {

        foreach ($this->format as $path => $fn) {
            $this->ArrayDots($fields, $path, $fn);
        }

        return $fields;
    }

    /**
     * Used to format fields
     *
     * @param array $arr
     * @param $path
     * @param $fn
     *
     * @return array|null
     */
    private function ArrayDots(array &$arr, $path, $fn) {
        $loc = &$arr;
        $dots = explode('.', $path);
        foreach($dots as $step)
        {
            array_shift($dots);
            if($step === '*' && is_array($loc)) {
                $new_loc = &$loc;
                $indies = array_keys($new_loc);
                foreach($indies as $index) {
                    if(isset($new_loc[$index])) {
                        $this->ArrayDots($new_loc[$index], implode('.', $dots), $fn);
                    }
                }
            } elseif( isset($loc[$step] ) ) {
                $loc = &$loc[$step];
            } else {
                return null;
            }

        }

        if(!isset($indies)) {
            if( is_callable($fn) ) {
                $loc = call_user_func($fn, $loc);
            } elseif( is_callable('\\TypeRocket\\Sanitize::' . $fn ) ) {
                $fn = '\\TypeRocket\\Sanitize::' . $fn;
                $loc = call_user_func($fn, $loc);
            }
        }

        return $loc;
    }

    /**
     * Get keys from TypeRocket brackets
     *
     * @param $str
     *
     * @return mixed
     */
    private function getDotKeys( $str )
    {
        $matches = explode('.', $str);

        return $matches;
    }

    /**
     * Get the value of a field if it is not an empty string or null.
     * If the field is null, undefined or and empty string it will
     * return null.
     *
     * @param $value
     *
     * @return null
     */
    protected function getValueOrNull( $value )
    {
        return ( isset( $value ) && $value !== '' ) ? $value : null;
    }

    /**
     * Create resource by TypeRocket fields
     *
     * When a resource is created the Model ID should be set to the
     * resource's ID.
     *
     * @param array $fields
     *
     * @return mixed
     */
    abstract function create( array $fields );

    /**
     * Update resource by TypeRocket fields
     *
     * @param array $fields
     *
     * @return mixed
     */
    abstract function update( array $fields );

    /**
     * Find resource by ID
     *
     * @param $id
     *
     * @return mixed|$this
     */
    abstract function findById( $id );

    /**
     * Get base field value
     *
     * Some fields need to be saved as serialized arrays. Getting
     * the field by the base value is used by Fields to populate
     * their values.
     *
     * This method must be implemented to return the base value
     * of a field if it is saved as a dot group.
     *
     * @param $field_name
     *
     * @return null
     */
    abstract protected function getBaseFieldValue( $field_name );

}