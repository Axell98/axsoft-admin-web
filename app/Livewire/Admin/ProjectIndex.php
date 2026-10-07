<?php

namespace App\Livewire\Admin;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Proyectos')]
class ProjectIndex extends Component
{
    use WithPagination;

    private const PER_PAGE = 12;

    #[Url(as: 'buscar')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleVisibility(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['is_published' => ! $project->is_published]);

        $this->dispatch('notify', message: $project->is_published ? 'Proyecto visible en el sitio web' : 'Proyecto oculto del sitio web');
    }

    public function delete(int $id): void
    {
        Project::findOrFail($id)->delete();

        $this->dispatch('notify', message: 'Proyecto eliminado');
    }

    public function render(): View
    {
        $term = trim($this->search);

        $projects = Project::query()
            ->with(['phases.mediaFile', 'category'])
            ->when($term !== '', function ($query) use ($term) {
                $like = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);

                $query->where(function ($query) use ($like) {
                    $query->whereRaw("title LIKE ? ESCAPE '!'", ["%{$like}%"])
                        ->orWhereHas('category', fn ($category) => $category->whereRaw("name LIKE ? ESCAPE '!'", ["%{$like}%"]))
                        ->orWhereRaw("location LIKE ? ESCAPE '!'", ["%{$like}%"]);
                });
            })
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return view('livewire.admin.project-index', [
            'projects' => $projects,
            'totalProjects' => Project::count(),
        ]);
    }
}
