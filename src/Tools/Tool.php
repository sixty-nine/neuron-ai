<?php

namespace NeuronAI\Tools;

use NeuronAI\Exceptions\MissingCallbackParameter;
use NeuronAI\Exceptions\ToolCallableNotSet;
use NeuronAI\Exceptions\ToolException;
use NeuronAI\StaticConstructor;

class Tool implements ToolInterface
{
    use StaticConstructor;

    /**
     * The list of callback function arguments.
     *
     * @var array<ToolProperty>
     */
    protected array $properties = [];

    /**
     * @var ?callable
     */
    protected $callback = null;

    /**
     * The arguments to pass in to the callback.
     *
     * @var array
     */
    protected array $inputs = [];

    /**
     * The call ID generated by the LLM.
     *
     * @var ?string
     */
    protected ?string $callId = null;

    /**
     * The result of the execution.
     *
     * @var string|null
     */
    protected string|null $result = null;

    /**
     * Tool constructor.
     *
     * @param string $name
     * @param string $description
     */
    public function __construct(
        protected string $name,
        protected string $description,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function addProperty(ToolProperty $property): ToolInterface
    {
        $this->properties[] = $property;
        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getRequiredProperties(): array
    {
        return \array_reduce($this->properties, function ($carry, ToolProperty $property) {
            if ($property->isRequired()) {
                $carry[] = $property->getName();
            }

            return $carry;
        }, []);
    }

    public function setCallable(callable $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    public function getInputs(): array
    {
        return $this->inputs ?? [];
    }

    public function setInputs(?array $inputs): self
    {
        $this->inputs = $inputs ?? [];
        return $this;
    }

    public function getCallId(): ?string
    {
        return $this->callId;
    }

    public function setCallId(?string $callId): self
    {
        $this->callId = $callId;
        return $this;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string|array $result): self
    {
        $this->result = is_array($result) ? \json_encode($result) : $result;
        return $this;
    }

    /**
     * Execute the client side function.
     *
     * @throws MissingCallbackParameter
     * @throws ToolCallableNotSet|ToolException
     */
    public function execute(): void
    {
        if (!is_callable($this->callback)) {
            throw new ToolCallableNotSet('No callback defined for execution.');
        }

        // Validate required parameters
        foreach ($this->properties as $property) {
            if ($property->isRequired() && ! \array_key_exists($property->getName(), $this->getInputs())) {
                throw new MissingCallbackParameter("Missing required parameter: {$property->getName()}");
            }
        }

        $this->setResult(
            \call_user_func($this->callback, ...$this->getInputs())
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'inputs' => !empty($this->inputs) ? $this->inputs : new \stdClass(),
            'callId' => $this->callId,
            'result' => $this->result,
        ];
    }
}
