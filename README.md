# Liip Serializer - A fast JSON serializer

**This project is Open Sourced based on work that we did initially as closed source at Liip, it may be lacking some documentation. We plan to add more documentation and support, including Symfony bundles in the near future. If there is anything that you need or have questions about we would love to see you open an issue! :)**

# What it supports
This serializer can convert between JSON and PHP objects and back. It uses reflection, Phpdoc and [JMS Serializer](https://github.com/schmittjoh/serializer/) annotations to generate PHP code for the conversion. JMS serializer groups and versions are supported for serializing but not for deserializing.

## Limitations
If you customized JMS Serializer with your own listeners or similar things, this serializer will not work for you. We made an effort to detect when unsupported features are used and raise an error, but recommend that you double check whether the Liip Serializer really produces the exact same as JMS when transforming your data.

# How it works
The Liip Serializer generates PHP code based on the PHP models that you specify. It uses the flexible `liip/metadata-parser` to gather metadata on the models. A separate file is generated for every version and serializer groups combination to move all logic to the code generation step. This serializer is fast even for massive object trees because the generated PHP code is very simplistic and specific to the usecase, rather than the complex, flexible callback structure that JMS serializer uses. The project we developped this for often has data with up to a megabyte of compact JSON data.

You can use the Liip Serializer stand alone. If you are already working with
JMS Serializer, you can also use the drop-in replacement for JMS serializer
[liip/serializer-jms-adapter](https://github.com/liip/serializer-jms-adapter).
The drop-in adapter implements the JMS interfaces and provides fallback to the
regular JMS serializer for missing generated files and on other errors.

# How to use it
You need to generate converter files whenever your models change. They follow a
naming scheme that allows the Liip Serializer to find them. Because the files
have to be pre-generated, you need to specify the exact list of classes,
serializer groups and versions you want to support.

Note: We plan to create a Symfony bundle to integrate the Liip Serializer into
Symfony.

## Generate your files
This step needs to be executed during the deployment phase and whenever your
models change.

```php
use Doctrine\Common\Annotations\AnnotationReader;
use Liip\MetadataParser\Builder;
use Liip\MetadataParser\Parser;
use Liip\MetadataParser\RecursionChecker;
use Liip\MetadataParser\ModelParser\JMSParser;
use Liip\MetadataParser\ModelParser\LiipMetadataAnnotationParser;
use Liip\MetadataParser\ModelParser\PhpDocParser;
use Liip\MetadataParser\ModelParser\ReflectionParser;
use Liip\Serializer\DeserializerGenerator;
use Liip\Serializer\Serializer;
use Liip\Serializer\SerializerGenerator;
use Liip\Serializer\Template\Deserialization;
use Liip\Serializer\Template\Serialization;

$configuration = GeneratorConfiguration::createFomArray([
    'default_group_combinations' => ['api'],
    'default_versions' => ['', '1', '2'],
    'classes' => [
         Product::class => [
             'default_versions' => ['1', '2'], // optional, falls back to global list
             'group_combinations' => [ // optional, falls back to global default_group_combinations
                 [
                     'groups' => [], // generate without groups
                 ],
                 [
                     'groups' => ['api'], // global groups are overwritten, not merged. versions are taken from class default
                 ],
                 [
                     'groups' => ['api', 'detail'],
                     'versions' => ['2'], // only generate the combination of api and detail for version 2
                 ],
             ],
         ],
         Other::class => [], // generate this class with default groups and versions
    ]
]);

$parsers = [
    new ReflectionParser(),
    new PhpDocParser(),
    new JMSParser(new AnnotationReader()),
    new LiipMetadataAnnotationParser(new AnnotationReader()),
];
$builder = new Builder(new Parser($parsers), new RecursionChecker(null, []));

$serializerGenerator = new SerializerGenerator( new Serialization(), $configuration, $cacheDirectory);
$deserializerGenerator = new DeserializerGenerator(new Deserialization(), [Product::class, User::class], $cacheDirectory);
$serializerGenerator->generate($builder);
$deserializerGenerator->generate($builder);
```

### Configuration format

Specify global defaults for the versions to be generated, and the group 
combinations.

Then specify for which classes to generate the serializer and deserializer.

For each class, you can overwrite which versions to generate. If you specify 
no group combinations, the global default group combinations are used. If you
specify group combinations, you can again overwrite the versions to generate.

Note that defaults are not merged - the most specific list is used exclusively.

#### Version

To generate a file without version, specify version `''` in the list of versions.

#### Groups

To generate a serializer without groups, specify an empty group combination `[]`.

## Serialize using the generated code
In this example, we serialize an object of class `Product` for version 2:

```php
use Acme\Model\Product;
use Liip\Serializer\Context;
use Liip\Serializer\Serializer;

$serializer = new Serializer($cacheDirectory);

// A model to serialize
$productModel = new Product();

// Your serialized data
$data = $serializer->serialize($productModel, 'json', (new Context())->setVersion(2));
```

## Deserialize using the generated code
```php
use Acme\Model\Product;
use Liip\Serializer\Serializer;

$serializer = new Serializer($cacheDirectory);

// Data to deserialize
$data = '{
    "api_string": "api",
    "detail_string": "details",
    "nested_field": {
        "nested_string": "nested"
    },
    "date": "2018-08-03T00:00:00+02:00",
    "date_immutable": "2016-06-01T00:00:00+02:00"
}';

/** @var Product $model */
$model = $serializer->deserialize($data, Product::class, 'json');
```

## Working with Arrays

Like JMS Serializer, the Liip Serializer also provides `fromArray` and
`toArray` for working with array data. As usual when using PHP arrays for JSON
data, you will lose the distinction between empty array and empty object.

# Where do I go for help?
If you need help, please open an issue on github.

# Background: Why an Object Serializer Generator?
We started having performance problem with large object structures (often
several Megabyte of JSON data). Code analysis showed that a lot of time is
spent calling the JMS callback structure hundred thousands of times.
Simplistic generated PHP code is much more efficient at runtime.

# Implementation Notes
The `DeserializerGenerator` and `SerializerGenerator` produce PHP code from the
metadata. The generators use twig to render the PHP code, for better
readability. See the `Template` namespace.

The indentation in the generated code is not respecting levels of nesting. We
could carry around the depth and prepend whitespace, but apart from debugging,
nobody will look at the generated code.

We decided to not use reflection, for better performance. Properties need to be
public or have a public getter for serialization. For deserialization, we also
match constructor arguments by name, so as long as a non-public property name
matches a constructor argument, it needs no setter.
