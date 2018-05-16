<?php

const USER_TOKEN_TTL = 120; // minutes
const DS = DIRECTORY_SEPARATOR;
const DEBUG = false;
const RANDOM_SALT = '*YAS*DO239ehsbu7';
const TOKEN_HEADER_FIELD = 'X-Authenticationtoken';

const AUTH_PERMISSION_DENIED = 'No valid token found';
const CONTROLLER_NOT_FOUND = 'Controller not found';
const ACTION_NOT_FOUND = 'Action not found';

const VALIDATE_REQUIRED = 'Property is required';
const VALIDATE_REQUIRED_EMPTY = 'Property can not be empty';
const VALIDATE_TYPE_INT = 'Property must be an integer';
const VALIDATE_TYPE_ALPHA = 'Property must be alpha only';

const VALIDATE_TYPE_INT_MIN = 'Minimum value of this integer is ';
const VALIDATE_TYPE_INT_MAX = 'Maximum value of this integer is ';
const VALIDATE_TYPE_VARCHAR = 'Property must be a string';
const VALIDATE_TYPE_VARCHAR_MINLENGTH = 'Minimum length of varchar is ';
const VALIDATE_TYPE_VARCHAR_MAXLENGTH  = 'Maximum length of varchar is ';
const VALIDATE_TYPE_TEXT = 'Property must be a string';
const VALIDATE_TYPE_ENUM = 'Enum value should be one of: ';
const VALIDATE_TYPE_BOOL = 'Property must be a boolean';
const VALIDATE_TYPE_UNKNOWN = 'Unknown type validator ';

const ACL_BRANCH_RESTRICTED = 'No permissions to access branch';
const ACL_COMPANY_RESTRICTED = 'No permissions to access branch';
const ACL_OFFICE_RESTRICTED = 'No permissions to access branch';


const MIGRATION_DIR = '/Migrations';