<?php

use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert the shim's tool side
        DB::table('tools')->insert([
            'id' => 1,
            'name' => 'LTI Shim Tool Side',
            'client_id' => 'Not used for shim, look up in platform_client',
            'target_link_uri' => config('app.url') . '/lti/target.php'
        ]);
        DB::table('tool_keys')->insert([
            'kid' => 'MyDummyKey',
            'tool_id' => 1,
            'key' => '{
                "kid": "MyDummyKey",
                "alg": "RS256",
                "use": "sig",
                "key_ops": [
                    "sign",
                    "verify"
                ],
                "kty": "RSA",
                "n": "vU48cmjHyrhMar2lJB6H2qchmv9pURfLvqn5Fxs31SnMt5lE5p1rv0CFlmKM3_0pRcX7qPFBXQIOnOF9Lu_9r5ryYR82yhvNUEJxoBgjEDbGyfCObanimqEN4EFw6-WALuX5iWI-dXwp6mmLuUeCxa92GwkWJ4fT1H8IZukzFpRBqsNYUv5XsLME0HIPCiptiMro3YLNpFRwHBMPH1XZXnfYaR1u3kmqBokPtopxpDQdWV5frG2hjW7ufoCFcyaMAiaYGQwQYlNRpR5zkTtAWQYNnDyAc-bXd06zO14GOWjk0F5Fo-uMBeAe1x_PkEYSZolApmVFYwYHNwidT1_y3uuKWQ_mMFHltb9h-4tlDdha6a14H4XCvzs_bcbaPqXOR997jRJQ6AOfzq87-4p5BSx4fNG6Gvup2JA9Kb7rJxr-16Dtpd-dVSLd8LK0NkW46MVZRtORKQE_BbifQ7BMFMb4LyPiZd-poKQwhcykf8U9-8-2TWrJw93laQt9i7aXHGj8k4_9A3B85cQ3CB7bK3bvtb8pwau0b0BWwl9-0MXJJhXCDXskhiGsOsSxfl_kcMiyCOyigtex1OohBrTlsEsgaUE_ujnbQ5aTC9YCYmDn7_KWgipC5eZnvWxjwQ3EumaVktvP1f5TaTJZ_Gv7X_EvCZwsIJLr0zS_JK_HfNk",
                "e": "AQAB",
                "d": "g0uV5QwDNfipF4c82FZMhzxPtH7c_p9wcmbVGk-IY9G_6L7m6MPaDSWlAW6MonWlgH0WVFAvs2BP3kMOXdWZr4fZ_750c6zgs3_B6vWSrBYxvbGYNzsSrYhyZU0-En50bxKWBdEb3MQnFivp-rE8Y_uUAKBGu1gbdVA99ZJcGpbtDJC726QIEce32RKil7xhh_Sjma61Dfp6Xx5g6KzUPA8HC7CcSpPw1uONAF_4_ZgfmvI3jRHkcIG17Asbg4gCpyM5bnXNj0SWZd0-7kWYFaZ7HhdgSAKdAmSVLuHBk2PK_zHDPzOJRDHzceTgQwI2lOMw8bdfbpSpwGO7J2g2u271mAx12V-WbFZzlF3-p1IFDxWnwgIlxxOsiFovm6vs9tUN6tKwD-Hm9ycF297v9ScAeARa1v-2RLug9eAlvLjnwcugevKvmvYPCLM-JOuXPqBuewwL0ybOyk-JEb1rXsC1V3p94K0ffEUt45CJOUaRfOHfdm1nLoZ6Y_coB4N-X7Icz7FgO-tInYOpbndpvcV_4RHRUywo05OCcqWRGsSUVtjUUJiCoBQygUoECs8PN6Sb0k3ijCr2gNfow9YvLkH09bBUdtRcYnkjOsiPhKOnhaHYT8swdvEVp1AjcltPwnPL_AV__GLZSPwUzoBiV9WyIZNxxkQNyAU-Iuhm-dk",
                "p": "8qCJ7NYgoY7E4Sk8g7ihKhYzv3Qjb0BfZTlHFtZV_TmuSVND4KnUBhuGZDhoDfEQg3kvE4r2S6qh4QC2kbwNuticvtZRWTFRHlkwIs0ecO4W9CxUM3pORwqP6h2ouD5gb6wrUSCRXm-hVu5q02JvLWtSZuxQgk5lO-BOelsIPeS16VNQVUpql921-YmYW6seVH4ebycy1caC466Le7eorn-FZjq6UbNse1PmBVoR76a4rqDHr_XU_Xnw3ldjabBQr3b8JpSfYDSC8mdzdn1ChnsXsfScKAXID1AuLht2z3uUqjHDH8jCHT0BFmh7jja_56_PWxqckhZsdBPBIwxPPw",
                "q": "x71VKEdS9h9AXYpoFaKjzbocS0_IcUEdRKD3GpL_eJSVnDaXkP-iLXxkPUne3I8NNecI9Z8UIypi9AFiMfkyAbJln22thDYHV6uhuCfttHDURkVzP17ZFysMX13VpB5DAxjUZBRScYX_84y8wg8ycWWJtFMJdnfyHbqRBevg9IkQyYGI_K1_lVxUXWM0h-dEXeuZ2ULhwP1HHXzYu5bDlTbjK9gl9EptVnH7fMv4DRr0PfU8XH2sb5aW6CwzUqu-6zPJGjSn7cjpdDWTX37sIgqoxxe8EFzNRTfXHvPZ7KGg5oPqTzox55x-8fOskH3IpOLLm-URqXf8y-Bc_bBF5w",
                "dp": "peWFSU5EM5NWJQq4GOzGLevOaEs161zdnW3joMbfNu0YsYRgdh5JHf_2DVLQOzAodW0H9lne_G_ojduZ-d3WmXqA-q-6Ib1p1NFw62rLNLZnNX-V9zjaixK6wn6THZ9w-wqC987H9wVnEPSmUHEOycSHLF7K0b5e0-kUX-8cnI1koOxZkmf2qG5tfflKFuvTKjyt_JyV0rwQRTPUpbw3d1E0WmkvequvMkh0kBY9C1s5DhZbU4JIpySYwL6J9_sZwFAgKxUWQIbHPo3j0hHlyeQimIhDuc3yNB5D5TzcKRQ72395BqOTrsBGnRbF3MCsyPNaWdXBVtZEQ746IgfJqw",
                "dq": "ITxO5DHiCxQuKKJ0KU1zxjK2rI1tA_NaxGWoWGKnYdAPiPvIgpcWzEzLfStCix6-iv6TjhNXTXarGoD0bu5RNNkTcdDVM4-t7xefBD6YnhK8L6k0wRGuO92s5F1_xBxI-EX0aWa8RSmP6l27turCcP39SOUcSPsybWHy6bJTLz1zbqRAQBM6jIbdfuCYCBkiViJy6iTn3a4HXE--3I_8oWKNsGj5_8k2olC7EQv7jWqzw2-ACL_cpoC_QYjBTXtIfGiUiOjEHJv2xKB5kkVTU2LErMQ1Rd-7RE5DXGIlG-vVEyZWIbLERQ7UTLxINY7IiS83xAv8wV820FHYB1qCdw",
            "qi": "LDCsUcj5gb_j1O4gVjAm1jbrkwlwebODWtIxjx3Bbk9RtsQmVMVmLnoyuyMXjkH3IOQen4cFHZIb1B0JdkwHagw8HEGkWZ1PpX_CzgA4FdaNso_dDAw94vkWVCY0iSCcgXaSLseWBpulwJr2L8CJDucBY7sCQ_KYcUKADZV0pptGqxQ4BnGHJQHfuhfX_2LSzbZmh8PKzFnKS5bZkG1uL0RlbcR9IJVwcRoRXlIyK39WyRnQ0eQwmdmO7m0jnUUJmMtgQcCbnGiiy_jjQabwv_WsXGv0cWZqLy8F5j-s1Rjrp-Xo4Gn_399tkEvn5dGlVQ8lcQ2reN8nIv0N6QKwbA"
            }'
        ]);
        
        // insert the lti 1.3 php example tool
        DB::table('tools')->insert([
            'id' => 2,
            'name' => 'LTI 1.3 PHP Example Tool',
            'client_id' => 'StrawberryCat',
            'target_link_uri' => 'http://localhost:9001/game.php',
        ]);
        // make sure the tool has a deployment
        DB::table('deployments')->insert([
            'deployment_id' => '1',
            'tool_id' => 2,
            'platform_id' => 2
        ]);
        //
    }
}
