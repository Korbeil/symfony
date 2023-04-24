AutoMapper Component
===============

Taken from [AutoMapper/AutoMapper](https://github.com/AutoMapper/AutoMapper)

> AutoMapper is a simple little library built to solve a deceptively complex
> problem - getting rid of code that mapped one object to another. This type
> of code is rather dreary and boring to write, so why not invent a tool to
> do it for us?

In PHP libraries and application mapping from one object to another is
fairly common:

* ObjectNormalizer / GetSetMethodNormalizer in Serializer component
* Mapping request data to object in Form component
* Hydrate object from SQL results in Doctrine
* Migrating legacy data to new model
* Mapping from database model to DTO objects (API / CQRS / ...)
* ...

The goal of this component is to offer an abstraction on top of this subject.
For that goal it provides an unique interface (other code is only
implementation detail):

```php
interface AutoMapperInterface
{
    /**
     * Map data from to target.
     *
     * @param array|object        $source  Any data object, which may be an object or an array
     * @param string|array|object $target  To which type of data, or data, the source should be mapped
     * @param Context             $context Options mappers have access to
     *
     * @return array|object The mapped object
     */
    public function map($source, $target, Context $context = null);
}
```

The source is from where the data comes from, it can be either an array
or an object.
The target is where the data should be mapped to, it can be either a string
(representing a type: array or class name) or directly an array or object
(in that case construction of the object is avoided).

Current implementation handle all of those possibilities at the exception
of the mapping from a dynamic object (array / stdClass) to another dynamic
object.

Resources
---------

 * [Documentation](https://symfony.com/doc/current/components/automapper/introduction.html)
 * [Contributing](https://symfony.com/doc/current/contributing/index.html)
 * [Report issues](https://github.com/symfony/symfony/issues) and
   [send Pull Requests](https://github.com/symfony/symfony/pulls)
   in the [main Symfony repository](https://github.com/symfony/symfony)
