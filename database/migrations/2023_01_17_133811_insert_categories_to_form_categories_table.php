<?php

use App\Models\FormTemplateCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertCategoriesToFormCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('form_template_categories')->insertOrIgnore([
            [
                'title' => 'Request Forms ',
                'slug' => 'request_forms'
            ],
            [
                'title' => 'Consent Forms',
                'slug' => 'consent_forms'
            ],
            [
                'title' => 'Intake Forms',
                'slug' => 'intake_forms'
            ],
            [
                'title' => 'Membership Forms',
                'slug' => 'membership_forms'
            ],
            [
                'title' => 'Voting Forms',
                'slug' => 'voting_forms'
            ],
            [
                'title' => 'Questionnaire Forms',
                'slug' => 'questionnaire_forms'
            ],
            [
                'title' => 'Order Forms',
                'slug' => 'order_forms'
            ],
            [
                'title' => 'Event Registration Forms',
                'slug' => 'event_registration_forms'
            ],
            [
                'title' => 'Booking Forms',
                'slug' => 'booking_forms'
            ],
            [
                'title' => 'Appointment Forms',
                'slug' => 'appointment_forms'
            ],
            [
                'title' => 'Agreement Forms',
                'slug' => 'agreement_forms'
            ],
            [
                'title' => 'Assessment Forms',
                'slug' => 'assessment_forms'
            ],
            [
                'title' => 'Evaluation Forms',
                'slug' => 'evaluation_forms'
            ],
            [
                'title' => 'Onboarding Forms',
                'slug' => 'onboarding_forms'
            ],
            [
                'title' => 'Enrollment Forms',
                'slug' => 'enrollment_forms'
            ],
            [
                'title' => 'Referral Forms',
                'slug' => 'referral_forms'
            ],
            [
                'title' => 'Report Forms',
                'slug' => 'report_forms'
            ],
            [
                'title' => 'Reservation Forms',
                'slug' => 'reservation_forms'
            ],
            [
                'title' => 'Sign-up Forms',
                'slug' => 'sign_up_forms'
            ],
            [
                'title' => 'Multi-Step Forms',
                'slug' => 'multi_step_forms'
            ],
            [
                'title' => 'Petition Forms',
                'slug' => 'petition_forms'
            ],
            [
                'title' => 'Donation Forms',
                'slug' => 'donation_forms'
            ],
            [
                'title' => 'RSVP Forms',
                'slug' => 'rsvp_forms'
            ],
            [
                'title' => 'File Upload Forms',
                'slug' => 'file_upload_forms'
            ],
            [
                'title' => 'Payment Forms',
                'slug' => 'payment_forms'
            ]
        ]);

        $calculatorForm = FormTemplateCategory::where('slug', 'calculatorform')->first();
        if ($calculatorForm) {
            $calculatorForm->title = 'Calculator Forms';
            $calculatorForm->save();
        }

        $leadQualification = FormTemplateCategory::where('slug', 'leadqualification')->first();
        if ($leadQualification) {
            $leadQualification->title = 'Lead Qualification Forms';
            $leadQualification->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $categoriesToDelete = array(
            'request_forms', 'consent_forms', 'intake_forms', 'membership_forms', 'voting_forms',
            'questionnaire_forms', 'order_forms', 'event_registration_forms', 'booking_forms',
            'appointment_forms', 'agreement_forms', 'assessment_forms', 'evaluation_forms', 'onboarding_forms',
            'enrollment_forms', 'referral_forms', 'report_forms', 'reservation_forms', 'sign_up_forms', 'multi_step_forms',
            'petition_forms', 'donation_forms', 'rsvp_forms', 'file_upload_forms', 'payment_forms'

        );
        DB::table('form_template_categories')->whereIn('slug', $categoriesToDelete)->delete();

        $calculatorForm = FormTemplateCategory::where('slug', 'calculatorform')->first();
        if ($calculatorForm) {
            $calculatorForm->title = 'Calculator Form';
            $calculatorForm->save();
        }

        $leadQualification = FormTemplateCategory::where('slug', 'leadqualification')->first();
        if ($leadQualification) {
            $leadQualification->title = 'Lead Qualification';
            $leadQualification->save();
        }
    }
}
