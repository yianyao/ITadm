<?php
/**
 * Created by PhpStorm.
 * User: yianyao
 * Date: 16-3-15
 * Time: 下午8:19
 *
 * 自动加载类
 **/
use com\itpdc\service\LoadException;
//require ("itpdc/service/LoadException.php");

function loadClass($class)
{

    try
    {
        $path = "d:\\wamp\\www\\lib\\";
        $file = $path . $class . ".php";
        if (!file_exists($file))
        {
            throw new LoadException("Not File exists!");
        }
        include $file;
    }
    catch (LoadException $e )
    {
        echo $e->getMessage() . "In" . $e->getFile() . $e->getLine();
    }
}
spl_autoload_register("loadClass");
/**
class load
{
    public function loadClass($class)
    {
        try
        {
            $path = "d:\\wamp\\www\\lib\\";
            $file = $path . $class . ".php";
            if (!file_exists($file))
            {
                throw new LoadException("Not File exists!");
            }
            include $file;
            throw new LoadException("Not File exists!");
        }
        catch (LoadException $e )
        {
            echo $e->getMessage() . "In" . $e->getFile() . $e->getLine();
        }
    }
    public static function load($class)
    {
        spl_autoload_register($this->loadClass($class));
    }
}
 **/
<?php
/**
 * @author Inhere
 * @version v1.0
 * Use : this
 * Date : 2015-1-10
 * 提供依赖注入的容器，注册、管理容器的服务。
 * 初次激活服务后默认共享，即后期获取时若不特别声明，都是获取已激活的服务实例
 * File: Container.php
 */

namespace ulue\core\ioc;

use Debug,
    ulue\core\ioc\helpers\IocHelper;

class Container implements InterfaceContainer, \ArrayAccess
{
    /**
     * 当前容器名称，初始时即固定
     * @var string
     */
    public $name;

    /**
     * 动态的存储当前正在设置或获取的服务 id, 用于支持实时的链式操作
     * @var string
     */
    public $id;

    /**
     * @see getNew()
     * true 强制获取服务的新实例，不管它是否有已激活的实例
     * @var bool
     */
    protected $getNewInstance = false;

    protected $state;

    /**
     * 当前容器的父级容器
     * @var Container
     */
    protected $parent =null;

    /**
     * 服务别名
     * @var array
     */
    protected $aliases = [];

    /**
     * $services 已注册的服务
     * $services = [
     *       'id' => [
     *           'callback' => 'a callback', 注册的服务回调
     *           'instance' => 'null | object', 注册的服务在第一次获取时被激活,只有共享的服务实例才会存储
     *           'shared' => 'a bool', 是否共享
     *           'locked' => 'a bool', 是否锁定
     *       ]
     *       ... ...
     *   ];
     * @var array
     */
    protected $services = [];

    /**
     * 服务参数设置 @see setArguments()
     * @var array
     */
    protected $arguments = [];

    /**
     * 后期绑定服务参数方式 (参数组成的数组。没有key,不能用合并)
     * 1. 用传入的覆盖 (默认)
     * 2. 传入指定了位置的参数来替换掉原有位置的参数
     * [
     *    //pos=>arguments
     *      0 => arg1, // 第一个参数
     *      1 => arg2,
     *      4 => arg3, //第五个参数
     * ]
     * 3. 在后面追加参数
     */
    const OVERLOAD_PARAM = 1;
    const REPLACE_PARAM  = 2;
    const APPEND_PARAM   = 3;

    public function __construct(Container $container=null)
    {
        $this->parent = $container;
    }


    /**
     * 在容器注册服务
     * @param  string $id 服务组件注册id
     * @param mixed(string|array|object|callback) $service 服务实例对象 | 需要的服务信息
     * sting:
     *  $service = classname
     * array:
     *  $service = [
     *     // 1. 仅类名 $service['params']则传入对应构造方法
     *     'target' => 'classname',
     *
     *     // 2. 类的静态方法, $service['params']则传入对应方法 classname::staticMethod(params..)
     *     // 'target' => 'classname::staticMethod',
     *
     *     // 3. 类的动态方法, $service['params']则传入对应方法 (new classname)->method(params...)
     *     // 'target' => 'classname->method',
     *
     *     'params' => [
     *         arg1,arg2,arg3,...
     *     ]
     *  ]
     * object:
     *  $service = new xxClass();
     * closure:
     *  $service = function(){ return xxx;};
     * @param bool $shared
     * @param bool $locked
     * @throws \DInstantiationException
     * @throws \DInvalidArgumentException
     * @return object $this
     */
    public function set($id, $service, $shared=false, $locked=false)
    {
        $this->_checkServiceId($id);

        // Debug::trace("i注册应用服务组件：[ $id  ] ",[
        //     '@param'             =>"\$id = {$id}",
        //     '已注册的服务ID：'     => array_keys($this->services)
        // ]);

        $this->id = $id = IocHelper::clearSpace($id);

        // 已锁定的服务
        if ( $this->isLocked($id) )
        {
            return $this;
        }

        // Log::record();
        // 已经是个服务实例 object 不是闭包 closure
        if ( is_object($service) && !is_callable($service))
        {
            $callback = function() use ($service) {
                return $service;
            };
        }
        else if ( is_callable($service) )
        {
            $callback = $service;
        }
        else if ( is_string($service) )
        {
            $callback = $this->createCallback($service);
        }
        else if ( is_array($service) )
        {
            $callback = $this->createCallback($service['target'], empty($service['params']) ? [] : $service['params'] );
        } else
        {
            throw new \DInvalidArgumentException('无效的参数！');
        }

        $config = [
            'callback' => $callback,
            'instance' =>  null,
            'shared'   => (bool) $shared,
            'locked'   => (bool) $locked
        ];

//        if(isset($config['arguments'])) {
//            $this->arguments[$id] = $config['arguments'];
//            unset($config['arguments']);
//        }


        $this->services[$id] = $config;

        return $this;

    }

    /**
     * 注册共享的服务
     * @param $id
     * @param $service
     * @return object
     * @throws \DInvalidArgumentException
     */
    public function share($id, $service)
    {
        return $this->set($id, $service, true);
    }

    /**
     * 注册受保护的服务 like class::lock()
     * @param  string $id [description]
     * @param $service
     * @param $share
     * @return $this
     */
    public function protect($id, $service, $share=false)
    {
        return $this->lock($id, $service, $share);
    }

    /**
     * (注册)锁定的服务，也可在注册后锁定,防止 getNew()强制重载
     * @param  string $id [description]
     * @param $service
     * @param $share
     * @return $this
     */
    public function lock($id, $service, $share=false)
    {
        return $this->set($id, $service, $share, true);
    }

    /**
     * 从类名创建服务实例对象，会尽可能自动补完构造函数依赖
     * @from windWalker https://github.com/ventoviro/windwalker
     * @param string $id a classname
     * @param  boolean $shared [description]
     * @throws \DDependencyResolutionException
     * @return object
     */
    public function createObject($id, $shared = false)
    {
        try
        {
            $reflection = new \ReflectionClass($id);
        }
        catch (\ReflectionException $e)
        {
            return false;
        }

        $constructor = $reflection->getConstructor();

        // If there are no parameters, just return a new object.
        if (is_null($constructor))
        {
            $callback = function () use ($id)
            {
                return new $id;
            };
        }
        else
        {
            $newInstanceArgs = $this->getMethodArgs($constructor);

            // Create a callable for the dataStore
            $callback = function () use ($reflection, $newInstanceArgs)
            {
                return $reflection->newInstanceArgs($newInstanceArgs);
            };
        }

        return $this->set($id, $callback, $shared)->get($id);
    }

    /**
     * 创建回调
     * @param $target
     * @param array $arguments
     * @return callable
     * @throws \DDependencyResolutionException
     * @throws \DInstantiationException
     */
    public function createCallback($target, array $arguments=[])
    {
        /**
         * @see $this->set() $service array 'target'
         */
        $target  = IocHelper::clearSpace($target);

        if ( strpos($target,'::')!==false )
        {
            $callback     = function () use ($target, $arguments)
            {
                return !$arguments ? call_user_func($target) : call_user_func_array($target, $arguments);
            };
        }
        else if ( ($pos=strpos($target,'->'))!==false )
        {
            $class        = substr($target, 0, $pos);
            $dynamicMethod= substr($target, $pos+2);

            $callback     = function () use ($class, $dynamicMethod, $arguments)
            {
                $object = new $class;

                return !$arguments ? $object->$dynamicMethod() : call_user_func_array([$object, $dynamicMethod], $arguments);
            };
        }

        if ( isset($callback) ) {
            return $callback;
        }

        // 仅是个 class name
        $class = $target;

        try
        {
            $reflection = new \ReflectionClass($class);
        }
        catch (\ReflectionException $e)
        {
            throw new \DInstantiationException($e->getMessage());
        }

        $constructor = $reflection->getConstructor();

        // If there are no parameters, just return a new object.
        if (is_null($constructor))
        {
            $callback = function () use ($class)
            {
                return new $class;
            };
        }
        else
        {
            $newInstanceArgs = $this->getMethodArgs($constructor);
            $newInstanceArgs = !$arguments ? $newInstanceArgs : $arguments;

            // Create a callable
            $callback = function () use ($reflection, $newInstanceArgs)
            {
                return $reflection->newInstanceArgs($newInstanceArgs);
            };
        }

        return $callback;
    }

    /**
     * @from windwalker https://github.com/ventoviro/windwalker
     * Build an array of constructor parameters.
     * @param   \ReflectionMethod $method Method for which to build the argument array.
     * @throws \DDependencyResolutionException
     * @return  array  Array of arguments to pass to the method.
     */
    protected function getMethodArgs(\ReflectionMethod $method)
    {
        $methodArgs = array();

        foreach ($method->getParameters() as $param)
        {
            $dependency = $param->getClass();
            $dependencyVarName = $param->getName();

            // If we have a dependency, that means it has been type-hinted.
            if (!is_null($dependency))
            {
                $dependencyClassName = $dependency->getName();

                // If the dependency class name is registered with this container or a parent, use it.
                if ($this->exists($dependencyClassName) !== null)
                {
                    $depObject = $this->get($dependencyClassName);
                }
                else
                {
                    $depObject = $this->createObject($dependencyClassName);
                }

                if ($depObject instanceof $dependencyClassName)
                {
                    $methodArgs[] = $depObject;

                    continue;
                }
            }

            // Finally, if there is a default parameter, use it.
            if ($param->isOptional())
            {
                $methodArgs[] = $param->getDefaultValue();

                continue;
            }

            // Couldn't resolve dependency, and no default was provided.
            throw new \DDependencyResolutionException(sprintf('Could not resolve dependency: %s', $dependencyVarName));
        }

        return $methodArgs;
    }
///////////////////////////////////////////////////////

    // self::setArguments() 别名方法
    public function setParams($id, array $params, $bindType=self::OVERLOAD_PARAM)
    {
        return $this->setArguments($id, $params, $bindType);
    }

    /**
     * 给服务设置参数，在获取服务实例前
     * @param string $id 服务id
     * @param array $params 设置参数
     * 通常无key值，按默认顺序传入服务回调中
     * 当 $bindType = REPLACE_PARAM
     * [
     * // pos => args
     *  0 => arg1,
     *  1 => arg2,
     *  3 => arg3,
     * ]
     * @param int $bindType 绑定参数方式
     * @throws \DInvalidArgumentException
     * @return $this
     */
    public function setArguments($id, array $params, $bindType=self::OVERLOAD_PARAM)
    {
        if (!$params)
        {
            return false;
        }

        $id = $this->resolveAlias($id);

        if ( ! $this->exists($id) )
        {
            throw new \DInvalidArgumentException("此容器 {$this->name} 中没有注册服务 {$id} ！");
        }

        if ( ($oldParams = $this->getParams($id))==null )
        {
            $this->arguments[$id] = (array) $params;
        }
        else
        {
            switch (trim($bindType))
            {
                case self::REPLACE_PARAM:
                    $nowParams = array_replace((array) $oldParams, (array) $params);
                    break;
                case self::APPEND_PARAM: {
                    $nowParams = (array) $oldParams;

                    foreach ($params as $param) {
                        $nowParams[] = $param;
                    }

                }
                    break;
                default:
                    $nowParams = (array) $params;
                    break;
            }

            $this->arguments[$id] = (array) $nowParams;
        }

        return $this;
    }

    /**
     * get 获取已注册的服务组件实例，
     * 共享服务总是获取已存储的实例，
     * 其他的则总是返回新的实例
     * @param  string $id 要获取的服务组件id
     * @param  array $params 如果参数为空，则会默认将 容器($this) 传入回调，可以在回调中直接接收
     * @param int $bindType @see $this->setArguments()
     * @throws \DNotFoundException
     * @return object | null
     */
    public function get($id, array $params= [], $bindType=self::OVERLOAD_PARAM)
    {
        if ( empty($id) || !is_string($id) ) {
            throw new \InvalidArgumentException(sprintf(
                'The 1 parameter must be of type string is not empty, %s given',
                gettype($id)
            ));
        }

        $this->id = $id = $this->resolveAlias($id);
        $this->setArguments($id, $params, $bindType);

        if ( !($config = $this->getService($id,false)) )
        {
            throw new \DNotFoundException(sprintf('服务id: %s不存在，还没有注册！',$id));
        }

        $callback = $config['callback'];
        $params   = $this->getArguments($id,false);

        // 是共享服务
        if( (bool) $config['shared'] )
        {
            if ( !$config['instance'] || $this->getNewInstance)
            {
                $this->services[$id]['instance'] = $config['instance'] = call_user_func_array(
                    $callback,
                    !$params ? [$this] : (array) $params
                );
            }

            $this->getNewInstance = false;

            return $config['instance'];
        }

        return call_user_func_array(
            $callback,
            !$params ? [$this] : (array) $params
        );
    }

    /**
     * getShared 总是获取同一个实例
     * @param $id
     * @throws \DNotFoundException
     * @return mixed
     */
    public function getShared($id)
    {
        $this->getNewInstance = false;

        return $this->get($id);

    }

    /**
     * 强制获取服务的新实例，针对共享服务
     * @param $id
     * @param array $params
     * @param int $bindType
     * @return null|object
     */
    public function getNew($id, array $params= [], $bindType=self::OVERLOAD_PARAM)
    {
        $this->getNewInstance = true;

        return $this->get($id, (array) $params, $bindType);
    }

    /**
     * 强制获取新的服务实例 like getNew()
     * @param string $id
     * @param array $params
     * @param int $bindType
     * @return mixed|void
     */
    public function make($id, array $params= [], $bindType=self::OVERLOAD_PARAM)
    {
        $this->getNewInstance = true;

        return $this->get($id, $params, $bindType);
    }

    /**
     * state description lock free protect shared
     * @return string $state
     */
    public function state()
    {
        return $this->state;
    }


    /**
     * state description lock free
     * @param $alias
     * @param string $id
     * @return $this [type] description
     */
    public function alias($alias, $id='')
    {
        if (empty($id))
        {
            $id = $this->id;
        }

        if ( !isset($this->aliases[$alias]) )
        {
            $this->aliases[$alias] = $id;
        }

        return $this;
    }

    /**
     * @param $alias
     * @return mixed
     */
    public function resolveAlias($alias)
    {
        return isset($this->aliases[$alias]) ? $this->aliases[$alias]: $alias;
    }

    /**
     * @return array $aliases
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * 注册一项服务(可能含有多个服务)提供者到容器中
     * @param  InterfaceServiceProvider $provider 在提供者内添加需要的服务到容器
     * @return $this
     */
    public function registerServiceProvider(InterfaceServiceProvider $provider)
    {
        $provider->register($this);

        return $this;
    }

    /**
     * @return static
     */
    public function createChild()
    {
        return new static($this);
    }

    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Method to set property parent
     * @param   Container $parent  Parent container.
     * @return  static  Return self to support chaining.
     */
    public function setParent(Container $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * 删除服务
     * @param $id
     * @internal param $ [type] $id [description]
     * @return void [type]       [description]
     */
    public function delete($id)
    {
        $id = $this->resolveAlias($id);

        if ( isset($this->services[$id]) )
        {
            unset($this->services[$id]);
        }

    }

    public function clear()
    {
        $this->services = [];
    }

    public function getArguments($id)
    {
        return $this->getParams($id);
    }

    public function getParams($id, $useAlias=true)
    {
        $useAlias && $id = $this->resolveAlias($id);

        return isset($this->arguments[$id])  ? $this->arguments[$id] : null;
    }

    public function getAllParams()
    {
        return $this->arguments;
    }

    /**
     * 获取某一个服务的信息
     * @param $id
     * @param bool $useAlias
     * @return array
     */
    public function getService($id, $useAlias=true)
    {
        $useAlias && $id = $this->resolveAlias($id);

        return !empty( $this->services[$id] ) ? $this->services[$id] : [];
    }

    /**
     * 获取全部服务信息
     * @return array
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * 获取全部服务名
     * @return array
     */
    public function getServiceNames()
    {
        return array_keys($this->services);
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * 获取全部服务id
     * @param bool $toArray
     * @return array
     */
    public function getIds($toArray=true)
    {
        $ids =  array_keys($this->services);

        return $toArray ? $ids : implode(', ', $ids);
    }

    public function isShared($id)
    {
        $config = $this->getService($id);

        return isset($config['shared']) ? (bool) $config['shared'] : false;
    }

    public function isLocked($id)
    {
        $config = $this->getService($id);

        return isset($config['locked']) ? (bool) $config['locked'] : false;
    }

    // 是已注册的服务
    public function isService($id)
    {
        $id = $this->resolveAlias($id);

        return !empty( $this->services[$id] ) ? true : false;
    }

    public function has($id)
    {
        return $this->isService($id);
    }

    public function exists($id)
    {
        return $this->isService($id);
    }

    protected function _checkServiceId($id)
    {
        if ( empty($id) )
        {
            throw new \DInvalidArgumentException( '必须设置服务Id名称！' );
        }

        if ( !is_string($id) || strlen($id)>30 )
        {
            throw new \DInvalidArgumentException( '设置服务Id只能是不超过30个字符的字符串！');
        }

        //去处空白和前后的'.'
        $id = trim( str_replace(' ','',$id), '.');

        if ( !preg_match("/^\w{1,20}(?:\.\w{1,20})*$/i", $id) )
        {
            throw new \DInvalidArgumentException( "服务Id {$id} 是无效的字符串！");
        }

        return $id;
    }

    public function __get($name)
    {
        if ($service = $this->get($name))
        {
            return $service;
        }

        throw new ContainerException("Getting a Unknown property! ".get_class($this)."::{$name}", 'get');
    }

    /**
     * Defined by IteratorAggregate interface
     * Returns an iterator for this object, for use with foreach
     * @return \ArrayIterator
     */
    //public function getIterator()
    //{
    //   return new \ArrayIterator($this->services);
    //}


    /**
     * Checks whether an offset exists in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  boolean  True if the offset exists, false otherwise.
     */
    public function offsetExists($offset)
    {
        return (boolean) $this->exists($offset);
    }

    /**
     * Gets an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  mixed  The array value if it exists, null otherwise.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Sets an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @param   mixed  $value   The array value.
     * @return  $this
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unset an offset in the iterator.
     * @param   mixed  $offset  The array offset.
     * @return  void
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

}