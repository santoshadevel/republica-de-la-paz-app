<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\CostCenter;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

/**
 * Accounting catalog from the PDF: income/expense categories (with
 * subcategories), cost centers and payment methods. Idempotent.
 */
class AccountingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories(TransactionType::Income, [
            'Membresías' => ['Membresía República', 'Pase Comunidad', 'Pase Ciudadano', 'Clase de prueba'],
            'Acompañamientos' => ['Psicología', 'Reiki', 'KAP', 'Sound Healing', 'Tarot', 'Diseño Humano', 'Medicina Ayurvédica', 'Masaje Ayurvédico', 'Fisioterapia'],
            'Eventos' => ['Workshops', 'Retiros', 'Charlas', 'Formaciones', 'Círculos'],
            'Alquiler de espacios' => ['Sala principal', 'Consultorios', 'Espacio para eventos'],
            'Tienda' => ['Libros', 'Inciensos', 'Mats', 'Accesorios'],
            'Café' => ['Bebidas', 'Comidas', 'Otros'],
        ]);

        $this->seedCategories(TransactionType::Expense, [
            'Honorarios' => ['Profesores', 'Terapeutas', 'Facilitadores invitados', 'Recepcionista', 'Salario de directoras'],
            'Infraestructura' => ['Alquiler', 'Electricidad', 'Agua', 'Internet', 'Línea celular', 'Limpieza', 'Seguridad'],
            'Marketing' => ['Redes sociales', 'Diseño gráfico', 'Publicidad', 'Fotografía y audiovisual', 'Página web'],
            'Administración' => ['Papelería', 'Software', 'Dominio web', 'Hosting', 'Licencias'],
            'Mantenimiento' => ['Reparaciones', 'Pintura', 'Jardinería', 'Equipamiento'],
            'Compras' => ['Material de yoga', 'Equipamiento', 'Decoración'],
            'Impuestos' => ['IVA', 'IRP', 'Otros'],
            'Gastos bancarios' => ['Comisiones', 'Transferencias', 'POS', 'Bancard'],
        ]);

        foreach (['Yoga', 'Terapias', 'Eventos', 'Tienda', 'Café', 'Administración'] as $name) {
            CostCenter::updateOrCreate(['name' => $name], ['is_active' => true]);
        }

        // Accounts / cash boxes where money actually sits.
        $cashBox = Account::updateOrCreate(['name' => 'Caja chica'], ['type' => AccountType::Cash->value]);
        $bank = Account::updateOrCreate(
            ['name' => 'Cuenta Banco 0082'],
            ['type' => AccountType::Bank->value, 'account_number' => '0082'],
        );

        // Payment methods, each routed to the account its money lands in.
        $methods = [
            'Efectivo' => $cashBox,
            'Transferencia bancaria' => $bank,
            'Bancard POS' => $bank,
            'Tarjeta de crédito' => $bank,
            'Tarjeta de débito' => $bank,
        ];

        foreach ($methods as $name => $account) {
            PaymentMethod::updateOrCreate(
                ['name' => $name],
                ['is_active' => true, 'default_account_id' => $account->id],
            );
        }
    }

    /**
     * @param  array<string, array<int, string>>  $tree
     */
    private function seedCategories(TransactionType $type, array $tree): void
    {
        foreach ($tree as $parentName => $children) {
            $parent = Category::updateOrCreate(
                ['name' => $parentName, 'parent_id' => null, 'type' => $type->value],
                ['is_active' => true],
            );

            foreach ($children as $childName) {
                Category::updateOrCreate(
                    ['name' => $childName, 'parent_id' => $parent->id, 'type' => $type->value],
                    ['is_active' => true],
                );
            }
        }
    }
}
