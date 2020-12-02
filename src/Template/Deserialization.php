<?php

declare(strict_types=1);

namespace Liip\Serializer\Template;

use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class Deserialization
{
    private const TMPL_FUNCTION = <<<'EOT'
<?php

function {{functionName}}(array {{jsonPath}}): {{className}}
{
    {{code}}

    return $model;
}

EOT;

    private const TMPL_CLASS = <<<'EOT'
{{initArgumentsCode}}
{{modelPath}} = new {{className}}({{arguments|join(', ')}});
{{code}}

EOT;

    private const TMPL_ARGUMENT = <<<'EOT'
{{variableName}} = {{default}};
{{code}}

EOT;

    private const TMPL_POST_METHOD = <<<'EOT'
{{modelPath}}->{{method}}();

EOT;

    private const TMPL_CONDITIONAL = <<<'EOT'
if (isset({{data}})) {
    {{code}}
}

EOT;

    private const TMPL_ASSIGN_JSON_DATA_TO_FIELD = <<<'EOT'
{{modelPath}} = {{jsonPath}};

EOT;

    private const TMPL_ASSIGN_JSON_DATA_TO_FIELD_CASTING = <<<'EOT'
{{modelPath}} = ({{type}}) {{jsonPath}};

EOT;

    private const TMPL_ASSIGN_DATETIME_TO_FIELD = <<<'EOT'
{{modelPath}} = new \DateTime({{jsonPath}});

EOT;

    private const TMPL_ASSIGN_DATETIME_FROM_FORMAT = <<<'EOT'
{{modelPath}} = \DateTime::createFromFormat('{{format}}', {{jsonPath}});

EOT;

    private const TMPL_ASSIGN_DATETIME_IMMUTABLE_TO_FIELD = <<<'EOT'
{{modelPath}} = new \DateTimeImmutable({{jsonPath}});

EOT;

    private const TMPL_ASSIGN_DATETIME_IMMUTABLE_FROM_FORMAT = <<<'EOT'
{{modelPath}} = \DateTimeImmutable::createFromFormat('{{format}}', {{jsonPath}});

EOT;

    private const TMPL_ASSIGN_SETTER = <<<'EOT'
{{modelPath}}->{{method}}({{value}});

EOT;

    private const TMPL_INIT_ARRAY = <<<'EOT'
{{modelPath}} = [];

EOT;

    private const TMPL_LOOP = <<<'EOT'
foreach (array_keys({{jsonPath}}) as {{indexVariable}}) {
    {{code}}
}

EOT;

    private const TMPL_UNSET = <<<'EOT'
unset({{variableNames|join(', ')}});

EOT;

    private const TMPL_EXTRACT = '{{jsonPath}} ?? {{default}}';

    private const TMPL_CREATE_OBJECT = 'new {{className}}({{arguments|join(\', \')}})';

    /**
     * @var Environment
     */
    private $twig;

    public function __construct()
    {
        $this->twig = new Environment(new ArrayLoader(), ['autoescape' => false]);
    }

    public function renderFunction(string $name, string $className, string $jsonPath, string $code): string
    {
        return $this->render(self::TMPL_FUNCTION, [
            'functionName' => $name,
            'className' => $className,
            'jsonPath' => $jsonPath,
            'code' => $code,
        ]);
    }

    public function renderClass(string $modelPath, string $className, array $arguments, string $code, string $initArgumentsCode = ''): string
    {
        return $this->render(self::TMPL_CLASS, [
            'modelPath' => $modelPath,
            'className' => $className,
            'arguments' => $arguments,
            'code' => $code,
            'initArgumentsCode' => $initArgumentsCode,
        ]);
    }

    public function renderArgument(string $variableName, string $default, string $code): string
    {
        return $this->render(self::TMPL_ARGUMENT, [
            'variableName' => $variableName,
            'default' => $default,
            'code' => $code,
        ]);
    }

    public function renderPostMethod(string $modelPath, string $method): string
    {
        return $this->render(self::TMPL_POST_METHOD, [
            'modelPath' => $modelPath,
            'method' => $method,
        ]);
    }

    public function renderConditional(string $data, string $code): string
    {
        return $this->render(self::TMPL_CONDITIONAL, [
            'data' => $data,
            'code' => $code,
        ]);
    }

    public function renderAssignJsonDataToField(string $modelPath, string $jsonPath): string
    {
        return $this->render(self::TMPL_ASSIGN_JSON_DATA_TO_FIELD, [
            'modelPath' => $modelPath,
            'jsonPath' => $jsonPath,
        ]);
    }

    public function renderAssignJsonDataToFieldWithCasting(string $modelPath, string $jsonPath, string $type): string
    {
        return $this->render(self::TMPL_ASSIGN_JSON_DATA_TO_FIELD_CASTING, [
            'modelPath' => $modelPath,
            'jsonPath' => $jsonPath,
            'type' => $type,
        ]);
    }

    public function renderAssignDateTimeToField(bool $immutable, string $modelPath, string $jsonPath): string
    {
        $template = $immutable ? self::TMPL_ASSIGN_DATETIME_IMMUTABLE_TO_FIELD : self::TMPL_ASSIGN_DATETIME_TO_FIELD;

        return $this->render($template, [
            'modelPath' => $modelPath,
            'jsonPath' => $jsonPath,
        ]);
    }

    public function renderAssignDateTimeFromFormat(bool $immutable, string $modelPath, string $jsonPath, string $format): string
    {
        $template = $immutable ? self::TMPL_ASSIGN_DATETIME_IMMUTABLE_FROM_FORMAT : self::TMPL_ASSIGN_DATETIME_FROM_FORMAT;

        return $this->render($template, [
            'modelPath' => $modelPath,
            'jsonPath' => $jsonPath,
            'format' => $format,
        ]);
    }

    public function renderExtract(string $jsonPath, string $default = 'null'): string
    {
        return $this->render(self::TMPL_EXTRACT, [
            'jsonPath' => $jsonPath,
            'default' => $default,
        ]);
    }

    public function renderCreateObject(string $className, array $arguments): string
    {
        return $this->render(self::TMPL_CREATE_OBJECT, [
            'className' => $className,
            'arguments' => $arguments,
        ]);
    }

    public function renderSetter(string $modelPath, string $method, string $value): string
    {
        return $this->render(self::TMPL_ASSIGN_SETTER, [
            'modelPath' => $modelPath,
            'method' => $method,
            'value' => $value,
        ]);
    }

    public function renderInitArray(string $modelPath): string
    {
        return $this->render(self::TMPL_INIT_ARRAY, [
            'modelPath' => $modelPath,
        ]);
    }

    public function renderLoop(string $jsonPath, string $indexVariable, string $code): string
    {
        return $this->render(self::TMPL_LOOP, [
            'jsonPath' => $jsonPath,
            'indexVariable' => $indexVariable,
            'code' => $code,
        ]);
    }

    /**
     * @param string[] $variableNames
     */
    public function renderUnset(array $variableNames): string
    {
        return $this->render(self::TMPL_UNSET, [
            'variableNames' => $variableNames,
        ]);
    }

    private function render(string $template, array $parameters): string
    {
        $tmpl = $this->twig->createTemplate($template);

        return $tmpl->render($parameters);
    }
}
