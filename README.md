# Liip Serializer - A "drop in" replacement to JMS serializer

**This project is Open Sourced based on work that we did initially as closed source at Liip, it may be lacking some documentation. We plan to add more documentation and support, including Symfony bundles in the near future. If there is anything that you need or have questions about we would love to see you open an issue! :)**

# What it supports
This serializer can convert JSON to PHP objects with Phpdoc and JMS annotations and convert PHP objects to JSON. JMS serializer groups and versions are only supported when serializing but not for deserializing.

# How it works
The Liip serializer generates PHP code based on the PHP models that you specify. It uses the flexible `liip/metadata-parser` to gather metadata on the models. A separate file is generated for every version and serializer groups combination to move all logic to the code generation step. This serializer is fast because the generated PHP code is very simplistic and specific to the usecase, and uses simple arrays rather than the complex object tree that JMS serializer does.

# How to use it
We plan to make this easier in the very near future, and if you use Symfony, we plan to create a bundle for this! Currently, you need to first generate your files, and then serialize/deserialize using those files with the serialize and deserialize functions created in them.

## Generate your files
The generated files follow the naming convention of `serialize_FQN_WITH_UNDERSCORES_group_group_versionnumber.php`;

```
        $classMetaData = [
            Product::class => [
                ['api'],
                ['api', 'product-details'],
            ],
            User::class => [
                ['api'],
            ],
        ];

        $versions = ['1', '2', '4'];

        $builder = new Builder(new Parser($parsers), new RecursionChecker(null, []));
        $templating = new Serialization();

        $serializerGenerator = new SerializerGenerator($templating, $versions, $classMetaData, '/tmp');
        $deserializerGenerator = new DeserializerGenerator($templating, [Product::class, User::class], '/tmp');
        $serializerGenerator->generate($builder);
        $deserializerGenerator->generate($builder);
```

## Serialize using the generated code

This would serialize product class, version 2: 

```
// There is a fallback to JMS; pass that in here as $originalSerializer. Along with the directory of your files, and a logger.
$generatedSerializer = new GeneratedSerializer($originalSerializer, $cacheDirectory, $logger);

// A model to serialize
$productModel = new Product();

// Your serialized data
$data = $generatedSerializer->serialize($productModel, 'json', SerializationContext::create()->setVersion(2));
```

## Deserialize using the generated code

```
// There is a fallback to JMS; pass that in here. Along with the directory of your files, and a logger.
$generatedSerializer = new GeneratedSerializer($originalSerializer, $cacheDirectory, $logger);

// Data to deserialize
$data = [
    'api_string' => 'api',
    'detail_string' => 'details',
    'nested_field' => ['nested_string' => 'nested'],
    'date' => '2018-08-03T00:00:00+02:00',
    'date_immutable' => '2016-06-01T00:00:00+02:00',
];

/** @var Product $model */
$model = $generatedSerializer->deserialize($data, Product::class, 'json');
```

## Where do I go for help?

If you need help, open an issue. 
