<?php

namespace HS\Console\Commands;

use Faker\Factory as Faker;
use Faker\Factory\Provider\en_US\Company;
use Illuminate\Console\Command;

class SendTestEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:sendtests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a bunch of random emails to different email addresses';

    protected $emails = ['matts-test-instance@helpspot.com', 'userscapeone@gmail.com', 'userscapetwo@gmail.com', 'userscapethree@gmail.com', 'userscapeone@outlook.com', 'matt@userscape.onmicrosoft.com', 'userscapeone@zohomail.com'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // The number of messages to generate for each email.
        $emailsPerAddress = 5;
//        $email = 'tester@ericlbarnes.com';
        foreach ($this->emails as $email) {
            for ($i = 0; $i < $emailsPerAddress; $i++) {
                $this->send($email);
            }
        }
    }

    protected function send($to)
    {
        $faker = Faker::create();
        return \Mail::html($this->buildBody($faker), function ($message) use ($to, $faker) {
            $message->from($faker->email, $faker->name);
            $message->to($to, $faker->name)->subject($faker->catchPhrase);
        });
    }

    protected function buildBody($faker)
    {
        return '<p>'.implode('</p><p>', $faker->paragraphs()).'</p>';
    }
}
