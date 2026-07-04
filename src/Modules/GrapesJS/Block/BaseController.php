<?php

declare(strict_types=1);

namespace PHPageBuilder\Modules\GrapesJS\Block;

use InvalidArgumentException;
use RuntimeException;
use PHPageBuilder\Contracts\PageContract;

abstract class BaseController
{
    /**
     * Block model.
     */
    protected BaseModel $model;

    /**
     * Current page.
     */
    protected PageContract $page;

    /**
     * Is running inside page builder.
     */
    protected bool $forPageBuilder = false;

    /**
     * Controller configuration.
     */
    protected array $config = [];

    /**
     * Controller metadata.
     */
    protected array $metadata = [];

    /**
     * Request data.
     */
    protected array $request = [];

    /**
     * Response data.
     */
    protected array $response = [];

    /**
     * Validation errors.
     */
    protected array $errors = [];

    /**
     * Controller initialized.
     */
    protected bool $initialized = false;

    /**
     * Controller enabled.
     */
    protected bool $enabled = true;

    /**
     * Constructor.
     */
    public function __construct(
        ?BaseModel $model = null,
        ?PageContract $page = null,
        bool $forPageBuilder = false
    ) {
        if ($model && $page) {
            $this->init($model, $page, $forPageBuilder);
        }
    }

    /**
     * Initialize controller.
     */
    public function init(
        BaseModel $model,
        PageContract $page,
        bool $forPageBuilder = false
    ): static {

        $this->model = $model;
        $this->page = $page;
        $this->forPageBuilder = $forPageBuilder;
        $this->initialized = true;

        $this->boot();

        return $this;
    }

    /**
     * Boot controller.
     */
    protected function boot(): void
    {
        $this->loadDefaults();
        $this->beforeBoot();
        $this->afterBoot();
    }

    /**
     * Load default configuration.
     */
    protected function loadDefaults(): void
    {
        $this->config = [
            'cache' => false,
            'debug' => false,
            'version' => '1.0.0',
            'author' => '',
            'enabled' => true,
        ];
    }

    /**
     * Called before boot.
     */
    protected function beforeBoot(): void
    {
    }

    /**
     * Called after boot.
     */
    protected function afterBoot(): void
    {
    }

    /**
     * Main request handler.
     */
    public function handleRequest(): mixed
    {
        $this->ensureInitialized();

        if (!$this->enabled) {
            throw new RuntimeException('Controller is disabled.');
        }

        $this->beforeHandle();

        $result = $this->process();

        $this->afterHandle($result);

        return $result;
    }

    /**
     * Actual processing.
     */
    abstract protected function process(): mixed;

    /**
     * Before request.
     */
    protected function beforeHandle(): void
    {
    }

    /**
     * After request.
     */
    protected function afterHandle(mixed $result): void
    {
    }

    /**
     * Ensure initialized.
     */
    protected function ensureInitialized(): void
    {
        if (!$this->initialized) {
            throw new RuntimeException(
                'Controller has not been initialized.'
            );
        }
    }

    /**
     * Get model.
     */
    public function getModel(): BaseModel
    {
        return $this->model;
    }

    /**
     * Set model.
     */
    public function setModel(BaseModel $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get page.
     */
    public function getPage(): PageContract
    {
        return $this->page;
    }

    /**
     * Set page.
     */
    public function setPage(PageContract $page): static
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Running inside builder?
     */
    public function isForPageBuilder(): bool
    {
        return $this->forPageBuilder;
    }

    /**
     * Enable controller.
     */
    public function enable(): static
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * Disable controller.
     */
    public function disable(): static
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Is enabled?
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set config.
     */
    public function setConfig(string $key, mixed $value): static
    {
        $this->config[$key] = $value;

        return $this;
    }

    /**
     * Get config.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set metadata.
     */
    public function setMetadata(string $key, mixed $value): static
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Get metadata.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Set request.
     */
    public function setRequest(array $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get request.
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Set response.
     */
    public function setResponse(array $response): static
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get response.
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * Add validation error.
     */
    public function addError(string $error): static
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Get errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Has errors?
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Clear errors.
     */
    public function clearErrors(): static
    {
        $this->errors = [];

        return $this;
    }

    /**
     * Validate.
     */
    protected function validate(): bool
    {
        return true;
    }

    /**
     * Reset controller.
     */
    public function reset(): static
    {
        $this->request = [];
        $this->response = [];
        $this->errors = [];
        $this->metadata = [];

        return $this;
    }

    /**
     * Log message.
     */
    protected function log(string $message): void
    {
        // Integrate with PSR-3 logger if needed.
    }

    /**
     * Magic getter.
     */
    public function __get(string $name): mixed
    {
        return $this->$name ?? null;
    }

    /**
     * Magic isset.
     */
    public function __isset(string $name): bool
    {
        return isset($this->$name);
    }

    /**
     * Export state.
     */
    public function toArray(): array
    {
        return [
            'initialized' => $this->initialized,
            'enabled' => $this->enabled,
            'forPageBuilder' => $this->forPageBuilder,
            'config' => $this->config,
            'metadata' => $this->metadata,
            'request' => $this->request,
            'response' => $this->response,
            'errors' => $this->errors,
        ];
    }

    /**
     * Import configuration.
     */
    public function fromArray(array $data): static
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Destroy controller state.
     */
    public function destroy(): void
    {
        $this->reset();
        $this->enabled = false;
    }
}
