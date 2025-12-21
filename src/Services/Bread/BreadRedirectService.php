<?php

namespace TCG\Voyager\Services\Bread;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TCG\Voyager\Models\DataType;

class BreadRedirectService
{
    public function resolveAfterSave(Request $request, ?string $redirectUrl, DataType $dataType, bool $canBrowse): RedirectResponse
    {
        if ($this->isSafeRedirectUrl($request, $redirectUrl)) {
            return redirect()->to($redirectUrl);
        }

        if ($canBrowse) {
            return redirect()->route("voyager.{$dataType->slug}.index");
        }

        return redirect()->back();
    }

    private function isSafeRedirectUrl(Request $request, ?string $redirectUrl): bool
    {
        if (!$redirectUrl || !is_string($redirectUrl)) {
            return false;
        }

        $parts = @parse_url($redirectUrl);
        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['host']) && $parts['host'] !== $request->getHost()) {
            return false;
        }

        return true;
    }
}
