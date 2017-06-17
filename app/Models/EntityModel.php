<?php namespace App\Models;

use Auth;
use Eloquent;
use Utils;
use Validator;

/**
 * Class EntityModel
 */
class EntityModel extends Eloquent
{
    /**
     * @var bool
     */
    public $timestamps = true;
    /**
     * @var array
     */
    protected $hidden = ['id'];

    /**
     * @var bool
     */
    public static $notifySubscriptions = true;

    /**
     * @param null $context
     * @return mixed
     */
    public static function createNew($context = null)
    {
        $className = get_called_class();
        $entity = new $className();

        if ($context) {
            $user = $context instanceof User ? $context : $context->user;
            $company = $context->loginaccount;
        } elseif (Auth::check()) {
            $user = Auth::user();
            $company = Auth::user()->loginaccount;
        } else {
            Utils::fatalError();
        }

        $entity->user_id = $user->id;
        $entity->company_id = $company->id;

        // store references to the original user/loginaccount to prevent needing to reload them
        $entity->setRelation('user', $user);
        $entity->setRelation('loginaccount', $company);

        if (method_exists($className, 'trashed')) {
            $lastEntity = $className::whereCompanyId($entity->company_id)->withTrashed();
        } else {
            $lastEntity = $className::whereCompanyId($entity->company_id);
        }

        $lastEntity = $lastEntity->orderBy('public_id', 'DESC')
            ->first();

        if ($lastEntity) {
            $entity->public_id = $lastEntity->public_id + 1;
        } else {
            $entity->public_id = 1;
        }

        return $entity;
    }

    /**
     * @param $publicId
     * @return mixed
     */
    public static function getPrivateId($publicId)
    {
        $className = get_called_class();

        return $className::scope($publicId)->withTrashed()->value('id');
    }

    /**
     * @return string
     */
    public function getActivityKey()
    {
        return '[' . $this->getEntityType() . ':' . $this->public_id . ':' . $this->getDisplayName() . ']';
    }

    public function entityKey()
    {
        return $this->public_id . ':' . $this->getEntityType();
    }

    /*
    public function getEntityType()
    {
        return '';
    }

    public function getNmae()
    {
        return '';
    }
    */

    /**
     * @param $query
     * @param bool $publicId
     * @param bool $companyId
     * @return mixed
     */
    public function scopeScope($query, $publicId = false, $companyId = false)
    {
        if (!$companyId) {
            $companyId = Auth::user()->company_id;
        }

        $query->where($this->getTable() . '.company_id', '=', $companyId);

        if ($publicId) {
            if (is_array($publicId)) {
                $query->whereIn('public_id', $publicId);
            } else {
                $query->wherePublicId($publicId);
            }
        }

        if (Auth::check() && !Auth::user()->hasPermission('view_all')) {
            $query->where(Utils::pluralizeEntityType($this->getEntityType()) . '.user_id', '=', Auth::user()->id);
        }

        return $query;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeWithArchived($query)
    {
        return $query->withTrashed()->where('is_deleted', '=', false);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->public_id;
    }

    /**
     * @return mixed
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * @param $entityType
     * @return string
     */
    public static function getClassName($entityType)
    {
        return 'App\\Models\\' . ucwords(Utils::toCamelCase($entityType));
    }

    /**
     * @param $entityType
     * @return string
     */
    public static function getTransformerName($entityType)
    {
        return 'App\\Ninja\\Transformers\\' . ucwords(Utils::toCamelCase($entityType)) . 'Transformer';
    }

    public function setNullValues()
    {
        foreach ($this->fillable as $field) {
            if (strstr($field, '_id') && !$this->$field) {
                $this->$field = null;
            }
        }
    }

    // converts "App\Models\Relation" to "relation_id"
    /**
     * @return string
     */
    public function getKeyField()
    {
        $class = get_class($this);
        $parts = explode('\\', $class);
        $name = $parts[count($parts) - 1];
        return strtolower($name) . '_id';
    }

    /**
     * @param $data
     * @param $entityType
     * @return bool|string
     */
    public static function validate($data, $entityType, $entity = false)
    {
        // Use the API request if it exists
        $action = $entity ? 'update' : 'create';
        $requestClass = sprintf('App\\Http\\Requests\\%s%sAPIRequest', ucwords($action), ucwords($entityType));
        if (!class_exists($requestClass)) {
            $requestClass = sprintf('App\\Http\\Requests\\%s%sRequest', ucwords($action), ucwords($entityType));
        }

        $request = new $requestClass();
        $request->setUserResolver(function () {
            return Auth::user();
        });
        $request->setEntity($entity);
        $request->replace($data);

        if (!$request->authorize()) {
            return trans('texts.not_allowed');
        }

        $validator = Validator::make($data, $request->rules());

        if ($validator->fails()) {
            return $validator->messages()->first();
        } else {
            return true;
        }
    }

}