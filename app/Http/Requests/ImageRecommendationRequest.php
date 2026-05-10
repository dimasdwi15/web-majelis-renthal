<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ImageRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Bisa diakses guest maupun user login
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp,heic',
                'max:5120', // Max 5MB
                // Rule 'dimensions' DIHAPUS — menyebabkan error "Path must not be empty"
                // karena getimagesize() gagal pada file multipart dari Flutter di Laragon/Windows.
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Gambar wajib diupload.',
            'image.file'     => 'File tidak valid.',
            'image.image'    => 'File harus berupa gambar.',
            'image.mimes'    => 'Format gambar harus JPEG, PNG, WEBP, atau HEIC.',
            'image.max'      => 'Ukuran gambar maksimal 5MB.',
        ];
    }

    /**
     * Override agar error validasi dikembalikan sebagai JSON konsisten.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
