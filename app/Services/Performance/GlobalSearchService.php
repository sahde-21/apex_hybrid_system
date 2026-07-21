<?php

namespace App\Services\Performance;

use App\Enums\ContactType;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Models\User;
use Illuminate\Support\Str;

class GlobalSearchService
{
    /**
     * @return array<int, array{module: string, label: string, items: list<array{id: int, title: string, subtitle: string|null, url: string|null}>}>
     */
    public function search(User $user, string $term): array
    {
        $term = trim($term);
        $min = (int) config('performance.search.min_length', 2);

        if (mb_strlen($term) < $min) {
            return [];
        }

        $safe = str_replace(['%', '_'], ['\\%', '\\_'], $term);
        $perModule = (int) config('performance.search.max_results_per_module', 5);
        $groups = [];

        if ($user->can('products.read')) {
            $items = Product::query()
                ->select(['id', 'name', 'sku'])
                ->where(function ($query) use ($safe) {
                    $query->where('name', 'like', "%{$safe}%")
                        ->orWhere('sku', 'like', "%{$safe}%");
                })
                ->orderBy('name')
                ->limit($perModule)
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'title' => $product->name,
                    'subtitle' => $product->sku,
                    'url' => route('products.index', ['search' => $product->sku], false),
                ])
                ->all();

            if ($items !== []) {
                $groups[] = $this->group('products', __('scf.products'), $items);
            }
        }

        if ($user->can('contacts.read')) {
            $customers = Contact::query()
                ->select(['id', 'name', 'company_name', 'email', 'type'])
                ->whereIn('type', [ContactType::Customer, ContactType::Both])
                ->where(function ($query) use ($safe) {
                    $query->where('name', 'like', "%{$safe}%")
                        ->orWhere('company_name', 'like', "%{$safe}%")
                        ->orWhere('email', 'like', "%{$safe}%");
                })
                ->orderBy('name')
                ->limit($perModule)
                ->get()
                ->map(fn (Contact $contact) => [
                    'id' => $contact->id,
                    'title' => $contact->name,
                    'subtitle' => $contact->company_name ?? $contact->email,
                    'url' => route('contacts.index', ['search' => $contact->name], false),
                ])
                ->all();

            if ($customers !== []) {
                $groups[] = $this->group('customers', __('scf.performance.search_customers'), $customers);
            }

            $suppliers = Contact::query()
                ->select(['id', 'name', 'company_name', 'email', 'type'])
                ->whereIn('type', [ContactType::Supplier, ContactType::Both])
                ->where(function ($query) use ($safe) {
                    $query->where('name', 'like', "%{$safe}%")
                        ->orWhere('company_name', 'like', "%{$safe}%")
                        ->orWhere('email', 'like', "%{$safe}%");
                })
                ->orderBy('name')
                ->limit($perModule)
                ->get()
                ->map(fn (Contact $contact) => [
                    'id' => $contact->id,
                    'title' => $contact->name,
                    'subtitle' => $contact->company_name ?? $contact->email,
                    'url' => route('contacts.index', ['search' => $contact->name], false),
                ])
                ->all();

            if ($suppliers !== []) {
                $groups[] = $this->group('suppliers', __('scf.performance.search_suppliers'), $suppliers);
            }
        }

        if ($user->can('quotations.read')) {
            $groups[] = $this->documentGroup(
                'quotations',
                __('scf.quotations'),
                Quotation::query(),
                'quotations.index',
                $safe,
                $perModule,
            );
        }

        if ($user->can('sale-orders.read')) {
            $groups[] = $this->documentGroup(
                'sale_orders',
                __('scf.sale_orders'),
                SaleOrder::query(),
                'sale-orders.index',
                $safe,
                $perModule,
            );
        }

        if ($user->can('invoices.read')) {
            $groups[] = $this->documentGroup(
                'invoices',
                __('scf.invoices'),
                Invoice::query(),
                'invoices.index',
                $safe,
                $perModule,
            );
        }

        if ($user->can('purchase-orders.read')) {
            $groups[] = $this->documentGroup(
                'purchase_orders',
                __('scf.purchase_orders'),
                PurchaseOrder::query(),
                'purchase-orders.index',
                $safe,
                $perModule,
            );
        }

        if ($user->can('bills.read')) {
            $groups[] = $this->documentGroup(
                'bills',
                __('scf.bills'),
                Bill::query(),
                'bills.index',
                $safe,
                $perModule,
            );
        }

        return array_values(array_filter($groups));
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array{module: string, label: string, items: list<array{id: int, title: string, subtitle: string|null, url: string|null}>}|null
     */
    private function documentGroup(
        string $module,
        string $label,
        $query,
        string $indexRoute,
        string $safe,
        int $limit,
    ): ?array {
        $items = $query
            ->select(['id', 'reference_number', 'status'])
            ->where('reference_number', 'like', "%{$safe}%")
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn ($model) => [
                'id' => $model->id,
                'title' => $model->reference_number,
                'subtitle' => is_object($model->status) ? $model->status->label() : (string) $model->status,
                'url' => route("{$indexRoute}", ['search' => $model->reference_number], false),
            ])
            ->all();

        return $items === [] ? null : $this->group($module, $label, $items);
    }

    /**
     * @param  list<array{id: int, title: string, subtitle: string|null, url: string|null}>  $items
     * @return array{module: string, label: string, items: list<array{id: int, title: string, subtitle: string|null, url: string|null}>}
     */
    private function group(string $module, string $label, array $items): array
    {
        return [
            'module' => $module,
            'label' => $label,
            'items' => $items,
        ];
    }
}
