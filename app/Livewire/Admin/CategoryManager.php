<?php

namespace App\Livewire\Admin;

use App\Models\ProjectCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Categorías')]
class CategoryManager extends Component
{
    public string $newName = '';

    public ?int $editingId = null;

    public string $editingName = '';

    public function add(): void
    {
        $name = $this->validName($this->newName, 'newName');

        ProjectCategory::create(['name' => $name, 'slug' => Str::slug($name)]);

        $this->newName = '';
        $this->dispatch('notify', message: 'Categoría creada');
    }

    public function startEdit(int $id): void
    {
        $category = ProjectCategory::findOrFail($id);

        $this->resetErrorBag();
        $this->editingId = $category->id;
        $this->editingName = $category->name;
    }

    public function cancelEdit(): void
    {
        $this->resetErrorBag();
        $this->editingId = null;
        $this->editingName = '';
    }

    public function saveEdit(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $category = ProjectCategory::findOrFail($this->editingId);
        $name = $this->validName($this->editingName, 'editingName', $category->id);

        // El slug cambia con el nombre: la web debe volver a pedir las categorías.
        $category->update(['name' => $name, 'slug' => Str::slug($name)]);

        $this->cancelEdit();
        $this->dispatch('notify', message: 'Categoría actualizada');
    }

    public function delete(int $id): void
    {
        // Los proyectos de esta categoría quedan «sin categoría» (la llave foránea los suelta).
        ProjectCategory::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->cancelEdit();
        }

        $this->dispatch('notify', message: 'Categoría eliminada');
    }

    /**
     * Valida y devuelve el nombre limpio; lanza el error sobre el campo indicado.
     */
    protected function validName(string $value, string $field, ?int $ignoreId = null): string
    {
        $name = trim($value);

        if ($name === '') {
            throw ValidationException::withMessages([$field => 'Escribe el nombre de la categoría.']);
        }

        if (mb_strlen($name) > 100) {
            throw ValidationException::withMessages([$field => 'El nombre no puede superar los 100 caracteres.']);
        }

        if (Str::slug($name) === '') {
            throw ValidationException::withMessages([$field => 'El nombre solo puede contener letras y números.']);
        }

        if (ProjectCategory::nameTaken($name, $ignoreId)) {
            throw ValidationException::withMessages([$field => 'Ya existe una categoría con ese nombre.']);
        }

        return $name;
    }

    public function render(): View
    {
        return view('livewire.admin.category-manager', [
            'categories' => ProjectCategory::query()->withCount('projects')->orderBy('name')->get(),
        ]);
    }
}
