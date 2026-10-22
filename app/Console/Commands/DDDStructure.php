<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DDDStructure extends Command
{
    protected $signature = 'make:ddd {context : The bounded context} {entity : The entity name}';

    protected $description = 'Creates full DDD/Hexagonal structure with base stubs, repository contracts, DTOs, auto-registers provider, and generates Pest tests';

    public function handle(): int
    {
        $contextArg = $this->argument('context');
        $entityArg = $this->argument('entity');

        if (! is_string($contextArg) || ! is_string($entityArg)) {
            $this->error('The context and entity arguments must be valid strings.');

            return self::FAILURE;
        }

        $context = ucfirst(strtolower($contextArg));
        $entity = ucfirst(strtolower($entityArg));

        $basePath = base_path("src/{$context}/{$entity}");

        $this->info("Building DDD architecture for {$context} -> {$entity}...");

        $directories = [
            'Domain/Entities',
            'Domain/ValueObjects',
            'Domain/Repositories',
            'Domain/Exceptions',
            'Domain/Events',
            'Application/UseCases',
            'Application/DTOs',
            'Application/Commands',
            'Application/Interfaces',
            'Infrastructure/Repositories',
            'Infrastructure/Models',
            'Infrastructure/Providers',
            'Infrastructure/Routes',
        ];

        foreach ($directories as $dir) {
            $path = "{$basePath}/{$dir}";
            File::makeDirectory($path, 0755, true, true);
            file_put_contents("{$path}/.gitkeep", '');
        }

        // 2. Generar Stubs base (Código inicial con PHP 8.4)
        $this->generateStubs($context, $entity, $basePath);

        // 3. Registrar el Provider automáticamente en bootstrap/providers.php
        $this->registerServiceProvider($context, $entity);

        // 4. Crear archivos espejo para Tests con Pest
        $this->generatePestTests($context, $entity);

        $this->info("DDD structure for {$context}/{$entity} successfully created and registered!");

        return Command::SUCCESS;
    }

    private function generateStubs(string $context, string $entity, string $basePath): void
    {
        // Excepción de dominio base
        $entityNamespace = "Src\\{$context}\\{$entity}\\Domain\\Entities";
        $entityContent = <<<PHP
<?php

declare(strict_types=1);

namespace {$entityNamespace};

final class {$entity}
{
    public function __construct(
        public readonly int|string \$id,
        // TODO: Add domain properties here
    ) {}
}
PHP;
        $this->createFile("{$basePath}/Domain/Entities/{$entity}.php", $entityContent);

        // 2.2. Interfaz del Repositorio de Dominio
        $repoNamespace = "Src\\{$context}\\{$entity}\\Domain\\Repositories";
        $repoName = "{$entity}RepositoryInterface";
        $repoContent = <<<PHP
<?php

declare(strict_types=1);

namespace {$repoNamespace};

use Src\\{$context}\\{$entity}\\Domain\\Entities\\{$entity};

interface {$repoName}
{
    public function findById(int|string \$id): ?{$entity};
    public function save({$entity} \$entity): void;
}
PHP;
        $this->createFile("{$basePath}/Domain/Repositories/{$repoName}.php", $repoContent);

        // 2.3. DTO de Aplicación
        $dtoNamespace = "Src\\{$context}\\{$entity}\\Application\\DTOs";
        $dtoName = "Create{$entity}DTO";
        $dtoContent = <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$dtoNamespace};

            final readonly class {$dtoName}
            {
                public function __construct(
                    // TODO: Add request parameters here
                ) {}
            }
            PHP;
        $this->createFile("{$basePath}/Application/DTOs/{$dtoName}.php", $dtoContent);
        $exceptionNamespace = "Src\\{$context}\\{$entity}\\Domain\\Exceptions";
        $exceptionName = "{$entity}NotFoundException";
        $exceptionContent = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$exceptionNamespace};

        use Exception;

        final class {$exceptionName} extends Exception
        {
            public static function withId(int|string \$id): self
            {
                return new self("{$entity} with id {\$id} not found.");
            }
        }
        PHP;
        $this->createFile("{$basePath}/Domain/Exceptions/{$exceptionName}.php", $exceptionContent);

        $providerNamespace = "Src\\{$context}\\{$entity}\\Infrastructure\\Providers";
        $providerName = "{$entity}ServiceProvider";
        $providerContent = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$providerNamespace};

        use Illuminate\Support\ServiceProvider;

        final class {$providerName} extends ServiceProvider
        {
            public function register(): void
            {
                // \$this->app->bind({$entity}RepositoryInterface::class, Eloquent{$entity}Repository::class);
            }

            public function boot(): void
            {
                //
            }
        }
        PHP;
        $this->createFile("{$basePath}/Infrastructure/Providers/{$providerName}.php", $providerContent);
    }

    private function createFile(string $filePath, string $content): void
    {
        file_put_contents($filePath, trim($content)."\n");

        // Si existe un .gitkeep en la misma carpeta, lo eliminamos porque ya hay un archivo real
        $dir = dirname($filePath);
        $gitkeepPath = "{$dir}/.gitkeep";
        if (File::exists($gitkeepPath)) {
            File::delete($gitkeepPath);
        }
    }

    private function registerServiceProvider(string $context, string $entity): void
    {
        $providersFile = base_path('bootstrap/providers.php');

        if (! File::exists($providersFile)) {
            return;
        }

        $providerClass = "Src\\{$context}\\{$entity}\\Infrastructure\\Providers\\{$entity}ServiceProvider::class";

        $content = File::get($providersFile);

        if (str_contains($content, $providerClass)) {
            return;
        }

        $pattern = '/return\s*\[\s*(.*?)\s*\]\s*;/s';

        if (preg_match($pattern, $content, $matches)) {
            $innerContent = trim($matches[1]);

            if ($innerContent === '') {
                $newInner = "    \\{$providerClass},";
            } else {
                $innerContent = rtrim($innerContent, ',');
                $newInner = "    {$innerContent},\n    \\{$providerClass},";
            }

            $updatedContent = "<?php\n\nreturn [\n".$newInner."\n];\n";

            File::put($providersFile, $updatedContent);
            $this->info("Auto-registered {$entity}ServiceProvider in bootstrap/providers.php");
        }
    }

    private function generatePestTests(string $context, string $entity): void
    {
        $testPath = base_path("tests/Unit/{$context}/{$entity}");
        File::makeDirectory($testPath, 0755, true, true);

        $testContent = <<<PHP
        <?php

        declare(strict_types=1);

        test('example unit test for {$entity}', function () {
            expect(true)->toBeTrue();
        });
        PHP;

        $this->createFile("{$testPath}/ExampleTest.php", $testContent);
        $this->info("Created Pest test suite for {$entity}");
    }
}
