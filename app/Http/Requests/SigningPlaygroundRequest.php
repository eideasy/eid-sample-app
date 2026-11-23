<?php

namespace App\Http\Requests;

use Closure;
use DOMDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class SigningPlaygroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Keep the form's human page number correct even without JavaScript.
        // Direct API-style submissions may continue using the zero-based field.
        if ($this->exists('pdf_page_display')) {
            $display = $this->input('pdf_page_display');
            if ($display === null || $display === '') {
                $this->merge(['pdf_page' => null]);
            } elseif (is_scalar($display) && filter_var($display, FILTER_VALIDATE_INT) !== false) {
                $this->merge(['pdf_page' => (int) $display - 1]);
            }
        }
    }

    public function rules(): array
    {
        $pdf = $this->input('containerType') === 'pdf';
        $xml = $this->input('containerType') === 'enveloped-xades';
        $ignoreAppearance = !$pdf || $this->boolean('hide_pdf_visual');

        return [
            'redirect_uri' => ['nullable', 'url'],
            'unsigned_file' => array_filter(['required', 'array', 'min:1', $pdf ? 'max:1' : null]),
            'unsigned_file.*' => array_filter(['bail', 'required', 'file', $pdf ? 'mimes:pdf' : null, $xml ? $this->validateXml(...) : null]),
            'signType' => ['required', Rule::in(['local', 'external', 'digest', 'eseal', 'multisign'])],
            'containerType' => ['required', Rule::in(['asice', 'pdf', 'enveloped-xades', 'asice-for-each-file'])],
            'pdf_x' => [Rule::excludeIf($ignoreAppearance), 'nullable', 'required_with:pdf_y', 'integer', 'min:0'],
            'pdf_y' => [Rule::excludeIf($ignoreAppearance), 'nullable', 'required_with:pdf_x', 'integer', 'min:0'],
            'pdf_page' => [Rule::excludeIf($ignoreAppearance), 'nullable', 'integer', 'min:0'],
            'pdf_page_display' => [Rule::excludeIf($ignoreAppearance), 'nullable', 'integer', 'min:1'],
            'hide_pdf_visual' => ['nullable', Rule::in(['on', '1', '0', 1, 0, true, false])],
            'simple_firstname' => ['nullable', 'string', 'max:255'],
            'simple_lastname' => ['nullable', 'string', 'max:255'],
            'simple_email' => ['nullable', 'email'],
            'simple_sms' => ['nullable', 'regex:/^\+\d{7,15}$/'],
        ];
    }

    private function validateXml(string $attribute, UploadedFile $file, Closure $fail): void
    {
        $document = new DOMDocument();
        $options = LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING;
        if (defined('LIBXML_NO_XXE')) {
            $options |= LIBXML_NO_XXE;
        }
        if (!$document->load($file->path(), $options) || $document->doctype !== null) {
            $fail('Enveloped XAdES signing requires a well-formed XML document without a document type declaration.');
        }
    }

    public function messages(): array
    {
        return [
            'unsigned_file.required' => 'Choose at least one file to sign.',
            'unsigned_file.max' => 'PDF signing accepts one PDF. Use an ASiC-E format to sign multiple files.',
            'unsigned_file.*.mimes' => 'PDF signing requires a PDF document.',
            'pdf_x.required_with' => 'Enter both X and Y, or leave both empty for automatic placement.',
            'pdf_y.required_with' => 'Enter both X and Y, or leave both empty for automatic placement.',
            'pdf_page.min' => 'The API page index must be zero or greater.',
        ];
    }
}
