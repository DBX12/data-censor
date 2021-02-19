<?php


namespace dbx12\jsonCensor\tests;


use ReflectionClass;
use ReflectionException;

trait ReflectionHelperTrait
{

    /**
     * Invokes an inaccessible method
     *
     * @param object $object
     * @param string $method
     * @param array  $args
     * @param bool   $revoke whether to make method inaccessible after execution
     * @return mixed
     * @throws ReflectionException
     * @see invokeStaticMethod for invoking static methods
     */
    protected function invokeMethod(object $object, string $method, array $args = [], bool $revoke = true)
    {
        $reflection      = new ReflectionClass(get_class($object));
        $reflectedMethod = $reflection->getMethod($method);
        $reflectedMethod->setAccessible(true);
        $result = $reflectedMethod->invokeArgs($object, $args);
        if ($revoke) {
            $reflectedMethod->setAccessible(false);
        }
        return $result;
    }

    /**
     * Gets an inaccessible object property
     *
     * @param      $object
     * @param      $propertyName
     * @param bool $revoke whether to make property inaccessible after getting
     * @return mixed
     * @throws ReflectionException
     */
    protected function getInaccessibleProperty($object, $propertyName, $revoke = true)
    {
        $class = new ReflectionClass($object);
        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $result = $property->getValue($object);
        if ($revoke) {
            $property->setAccessible(false);
        }
        return $result;
    }
}
