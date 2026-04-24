<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesPageRequest;
use App\Models\SalesPage;
use App\Services\ContextManager;
use App\Services\SalesPageGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SalesPageController extends Controller
{
    public function index(Request $request)
    {
        $pages = $request->user()->salesPages()->paginate(12);

        return view('sales-pages.index', ['pages' => $pages]);
    }

    public function create(Request $request, ContextManager $context)
    {
        $bundle = $context->buildForUser($request->user());

        return view('sales-pages.create', ['context' => $bundle]);
    }

    public function store(StoreSalesPageRequest $request, SalesPageGenerator $generator)
    {
        $input = $request->normalized();

        try {
            $result = $generator->generate($input, $request->user());
        } catch (\Throwable $e) {
            Log::error('Sales page generation failed', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['generation' => 'Gagal menghasilkan halaman: '.$e->getMessage()]);
        }

        $page = SalesPage::create([
            'user_id' => $request->user()->id,
            'product_name' => $input['product_name'],
            'input_data' => $input,
            'generated_content' => $result['content'],
            'context_summary' => $result['context_summary'],
        ]);

        return redirect()
            ->route('sales-pages.show', $page)
            ->with('status', 'Halaman berhasil dibuat dengan konteks dari riwayat Anda.');
    }

    public function show(Request $request, SalesPage $salesPage)
    {
        $this->authorizeOwnership($request, $salesPage);

        return view('sales-pages.show', ['page' => $salesPage]);
    }

    public function preview(Request $request, SalesPage $salesPage)
    {
        $this->authorizeOwnership($request, $salesPage);

        return view('sales-pages.preview', ['page' => $salesPage]);
    }

    public function destroy(Request $request, SalesPage $salesPage)
    {
        $this->authorizeOwnership($request, $salesPage);
        $salesPage->delete();

        return redirect()->route('sales-pages.index')->with('status', 'Halaman dihapus.');
    }

    private function authorizeOwnership(Request $request, SalesPage $page): void
    {
        if ($page->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
