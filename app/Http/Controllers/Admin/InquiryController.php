<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::latest()->paginate(20);
        return view('admin.inquiries.index', compact('inquiries'));
    }

    public function markHandled(Inquiry $inquiry)
    {
        $inquiry->update(['is_handled' => true]);
        return back()->with('success', 'Inquiry ditandai selesai.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'inquiries-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Phone', 'Subject', 'Message', 'Handled', 'Created At']);
            Inquiry::chunk(100, function ($rows) use ($file): void {
                foreach ($rows as $row) {
                    fputcsv($file, [
                        $row->name, $row->email, $row->phone, $row->subject,
                        $row->message, $row->is_handled ? 'yes' : 'no', $row->created_at,
                    ]);
                }
            });
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
