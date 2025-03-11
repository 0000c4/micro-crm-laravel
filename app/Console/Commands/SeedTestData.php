<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class SeedTestData extends Command
{
    /**
     * Имя консольной команды.
     *
     * @var string
     */
    protected $signature = 'seed:test-data {--products=20 : Количество товаров} {--warehouses=3 : Количество складов}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Заполнить базу данных тестовыми данными для товаров, складов и остатков';

    /**
     * Создание экземпляра команды.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Выполнение консольной команды.
     *
     * @return int
     */
    public function handle()
    {
        $productsCount = (int)$this->option('products');
        $warehousesCount = (int)$this->option('warehouses');
        
        $this->info('Начало заполнения базы данных тестовыми данными...');
        
        // Очистка таблиц перед заполнением
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Stock::truncate();
        Product::truncate();
        Warehouse::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->info('Создание складов...');
        $warehouses = [];
        for ($i = 1; $i <= $warehousesCount; $i++) {
            $warehouse = Warehouse::create([
                'name' => 'Склад ' . $i
            ]);
            $warehouses[] = $warehouse;
            $this->info("Создан склад: {$warehouse->name}");
        }
        
        $this->info('Создание товаров и остатков...');
        $productBar = $this->output->createProgressBar($productsCount);
        $productBar->start();
        
        for ($i = 1; $i <= $productsCount; $i++) {
            // Создаем товар
            $product = Product::create([
                'name' => 'Товар ' . $i,
                'price' => rand(100, 10000) / 100
            ]);
            
            // Создаем остатки для всех складов
            foreach ($warehouses as $warehouse) {
                Stock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => rand(0, 100)
                ]);
            }
            
            $productBar->advance();
        }
        
        $productBar->finish();
        $this->newLine();
        
        $this->info('Заполнение базы данных тестовыми данными завершено.');
        $this->info("Создано {$productsCount} товаров на {$warehousesCount} складах.");
        
        return 0;
    }
}