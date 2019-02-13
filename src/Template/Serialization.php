<?php

declare(strict_types=1);

namespace Liip\Serializer\Template;

use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class Serialization
{
    private const TMPL_FUNCTION = <<<'EOT'
<?php

function {{functionName}}({{className}} $model, bool $useStdClass = true)
{
    $emptyHashmap = $useStdClass ? new \stdClass() : [];
    $emptyObject = $useStdClass ? new \stdClass() : [];

    {{code}}

    return $jsonData;
}

EOT;

    private const TMPL_CLASS = <<<'EOT'
$jsonData{{jsonPath}} = [];
{{code}}
if (0 === \count($jsonData{{jsonPath}})) {
    $jsonData{{jsonPath}} = $emptyObject;
}

EOT;

    private const TMPL_CONDITIONAL = <<<'EOT'
if (null !== {{condition}}) {
    {{code}}
}

EOT;

    private const TMPL_ASSIGN = <<<'EOT'
$jsonData{{jsonPath}} = {{propertyAccessor}};
EOT;

    private const TMPL_LOOP_ARRAY = <<<'EOT'
$jsonData{{jsonPath}} = [];
foreach (array_keys({{propertyAccessor}}) as {{indexVariable}}) {
    {{code}}
}

EOT;

    private const TMPL_LOOP_ARRAY_EMPTY = <<<'EOT'
$jsonData{{jsonPath}} = [];

EOT;

    private const TMPL_LOOP_HASHMAP = <<<'EOT'
if (0 === \count({{propertyAccessor}})) {
    $jsonData{{jsonPath}} = $emptyHashmap;
} else {
    foreach (array_keys({{propertyAccessor}}) as {{indexVariable}}) {
        {{code}}
    }
}

EOT;
    private const TMPL_LOOP_HASHMAP_EMPTY = <<<'EOT'
$jsonData{{jsonPath}} = $emptyHashmap;

EOT;

    private const TMPL_GETTER = '{{modelPath}}->{{method}}()';

    private const TMPL_DATETIME = '{{propertyPath}}->format(\'{{format}}\');';

    private const TMPL_TEMP_VAR = '${{name}} = {{value}}';

    /**
     * @var Environment
     */
    private $twig;

    public function __construct()
    {
        $this->twig = new Environment(new ArrayLoader(), ['autoescape' => false]);
    }

    public function renderFunction(string $name, string $className, string $code): string
    {
        return $this->render(self::TMPL_FUNCTION, [
            'functionName' => $name,
            'className' => $className,
            'code' => $code,
        ]);
    }

    public function renderClass(string $jsonPath, string $code): string
    {
        return $this->render(self::TMPL_CLASS, [
            'jsonPath' => $jsonPath,
            'code' => $code,
        ]);
    }

    public function renderConditional(string $condition, string $code): string
    {
        return $this->render(self::TMPL_CONDITIONAL, [
            'condition' => $condition,
            'code' => $code,
        ]);
    }

    public function renderAssign(string $jsonPath, string $propertyAccessor): string
    {
        return $this->render(self::TMPL_ASSIGN, [
            'jsonPath' => $jsonPath,
            'propertyAccessor' => $propertyAccessor,
        ]);
    }

    public function renderLoopArray(string $jsonPath, string $propertyAccessor, string $indexVariable, string $code): string
    {
        return $this->render(self::TMPL_LOOP_ARRAY, [
            'jsonPath' => $jsonPath,
            'propertyAccessor' => $propertyAccessor,
            'indexVariable' => $indexVariable,
            'code' => $code,
        ]);
    }

    public function renderLoopArrayEmpty(string $jsonPath): string
    {
        return $this->render(self::TMPL_LOOP_ARRAY_EMPTY, [
            'jsonPath' => $jsonPath,
        ]);
    }

    public function renderLoopHashmap(string $jsonPath, string $propertyAccessor, string $indexVariable, string $code): string
    {
        return $this->render(self::TMPL_LOOP_HASHMAP, [
            'jsonPath' => $jsonPath,
            'propertyAccessor' => $propertyAccessor,
            'indexVariable' => $indexVariable,
            'code' => $code,
        ]);
    }

    public function renderLoopHashmapEmpty(string $jsonPath): string
    {
        return $this->render(self::TMPL_LOOP_HASHMAP, [
            'jsonPath' => $jsonPath,
        ]);
    }

    public function renderGetter(string $modelPath, string $method): string
    {
        return $this->render(self::TMPL_GETTER, [
            'modelPath' => $modelPath,
            'method' => $method,
        ]);
    }

    public function renderDateTime(string $propertyPath, string $format): string
    {
        return $this->render(self::TMPL_DATETIME, [
            'propertyPath' => $propertyPath,
            'format' => $format,
        ]);
    }

    public function renderTempVariable(string $name, string $value): string
    {
        return $this->render(self::TMPL_TEMP_VAR, [
            'name' => $name,
            'value' => $value,
        ]);
    }

    public function renderConditionalUsingTempVariable(string $tempVariable, string $propertyAccessor, string $code): string
    {
        return $this->render(self::TMPL_CONDITIONAL, [
            'condition' => $this->renderTempVariable($tempVariable, $propertyAccessor),
            'code' => $code,
        ]);
    }

    private function render(string $template, array $parameters): string
    {
        $tmpl = $this->twig->createTemplate($template);

        return $tmpl->render($parameters);
    }
}
