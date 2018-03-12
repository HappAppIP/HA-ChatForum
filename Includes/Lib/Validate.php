<?php
//public $paramsAuthenticate = [
//    'varchar_value' => [
//        'required' => true,     // optional
//        'allow_empty' => false, // optional
//        'type' => 'varchar',   // required
//        'htmlentities' => true,    // optional
//        'max_length' => ,   // optional
//        'min_length' => ,     // optional
//    ],
//    'company_id' => [
//        'required' => true,  // optional
//        'type' => 'int',     //required
//        'min' =>             // optional
//        'max' =>             // optional
//    ],
//    'type' => [
//        'required' => true,  // optional
//        'type' => 'enum',    // required
//        'enum' => ['chat', 'forum'] // required
//    ],
//];


namespace Lib;

class Validate{

    protected $_errors = [];
    /**
     * @param array $ruleset
     * @param array $parameters
     * @return bool
     */
    public function validate(array $ruleset, array &$parameters)
    {
        $status = true;
        foreach ($ruleset as $key => $validators) {
            try {
                $this->_validateRequired($key, $validators, $parameters);
                $this->_validateType($key, $validators, $parameters);
            } catch (\UnexpectedValueException $e) {
                $status = false;
                continue;
            }
        }
        foreach($parameters as $k => $v){
            if(!isset($ruleset[$k])){
                unset($parameters[$k]);
            }
        }
        return $status;
    }

    public function getErrors(){
        return $this->_errors;
    }

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @return bool
     * @throws \UnexpectedValueException
     */
    protected function _validateRequired($key, $validators, &$parameters){
        if(isset($parameters[$key])){
            if(!is_object($parameters[$key]) && !is_array($parameters[$key])){
                if(empty(trim($parameters[$key]))&&$parameters[$key]!==0&&$parameters[$key]!=="0") {
                    if (!isset($validators['allow_empty']) || $validators['allow_empty'] == false) {
                        unset($parameters[$key]);
                        $this->_errors[$key] = VALIDATE_REQUIRED_EMPTY;
                        $this->_throw();
                    }
                }
            }
        }elseif(!isset($validators['required']) || $validators['required'] === true) {
            unset($parameters[$key]);
            $this->_errors[$key] = VALIDATE_REQUIRED;
            $this->_throw();
        }elseif(isset($validators['default'])){
            $parameters[$key] = $validators['default'];
        }
        return true;
    }

    /**
     * @param $key
     * @param $validators
     * @param $parameters
     * @return bool
     * @throws \UnexpectedValueException
     */
    protected function _validateType($key, $validators, &$parameters){
        if(!isset($parameters[$key])){
            return true;
        }
        $value = $parameters[$key];
        switch($validators['type']){
            case('int'):
                if(!ctype_digit($value) && !is_int($value)){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_INT;
                    $this->_throw();
                }
                if(isset($validators['min']) && $value < $validators['min']){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_INT_MIN . $validators['min'];
                    $this->_throw();
                }
                if(isset($validators['max']) && $value > $validators['max']){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_INT_MAX . $validators['max'];
                    $this->_throw();
                }
                $parameters[$key] = (int) $parameters[$key];
                break;
            case('varchar'):
                if(!is_string($parameters[$key]) && !ctype_digit($value)){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_VARCHAR;
                    $this->_throw();
                }
                if(isset($validators['min_length']) && strlen($value) < $validators['min_length']){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_VARCHAR_MINLENGTH . $validators['min_length'];
                    $this->_throw();
                }
                if(!isset($validators['max_length'])){
                    $validators['max_length'] = 255;
                }
                if(strlen($value)> $validators['max_length']){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_VARCHAR_MAXLENGTH . $validators['max_length'];
                    $this->_throw();
                }
                if(!isset($validators['htmlentities'])||$validators['htmlentities']===true){
                    $parameters[$key] = htmlentities($parameters[$key]);
                }
                break;
            case('text'):
                if(!is_string($parameters[$key]) && !ctype_digit($value)){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_TEXT;
                    $this->_throw();
                }
                if(!isset($validators['htmlentities'])||$validators['htmlentities']===true){
                    $parameters[$key] = htmlentities($parameters[$key]);
                }
                break;
            case('enum'):
                if(!in_array($parameters[$key], $validators['enum'])){
                    unset($parameters[$key]);
                    $this->_errors[$key] = VALIDATE_TYPE_ENUM . htmlentities(implode(', ', $validators['enum']));
                    $this->_throw();
                }
                break;
            default:
                unset($parameters[$key]);
                $this->_errors[$key] = VALIDATE_TYPE_UNKNOWN . htmlentities($validators['type']);
                $this->_throw();
                // break; disable break so coverage reports is 100% ;p
        }
        return true;
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function _throw(){
        throw new \UnexpectedValueException('Validation error');
    }
}