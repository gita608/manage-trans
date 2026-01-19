<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PublicPagesController extends Controller
{
    /**
     * Display the privacy policy page.
     *
     * @return \Illuminate\View\View
     */
    public function privacyPolicy()
    {
        return view('public.privacy-policy');
    }

    /**
     * Display the contact us page.
     *
     * @return \Illuminate\View\View
     */
    public function contactUs()
    {
        return view('public.contact-us');
    }

    /**
     * Handle contact form submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('contact-us')
                ->withErrors($validator)
                ->withInput();
        }

        // Get admin email from settings or use default company email
        $adminEmail = getSetting('admin_email', 'info@m2mservicesuae.com');
        
        try {
            // Format email content
            $emailContent = "New Contact Form Submission\n\n";
            $emailContent .= "Name: {$request->name}\n";
            $emailContent .= "Email: {$request->email}\n";
            $emailContent .= "Subject: {$request->subject}\n\n";
            $emailContent .= "Message:\n{$request->message}\n";
            
            // Send email notification
            Mail::raw($emailContent, function ($message) use ($request, $adminEmail) {
                $message->to($adminEmail)
                    ->subject('Contact Form: ' . $request->subject)
                    ->replyTo($request->email, $request->name);
            });

            return redirect()->route('contact-us')
                ->with('success', 'Thank you for contacting us! We will get back to you soon.');
        } catch (\Exception $e) {
            return redirect()->route('contact-us')
                ->with('error', 'Sorry, there was an error sending your message. Please try again later.')
                ->withInput();
        }
    }
}
