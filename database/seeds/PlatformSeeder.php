<?php

use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert the shim and associated key
        DB::table('platforms')->insert([
            'name' => 'LTI Shim Platform Side',
            'iss' => config('lti.iss'),
            'auth_req_url' => config('app.url') .
                config('lti.platform_launch_auth_req_path'),
            'jwks_url' => config('app.url') . config('lti.platform_jwks_path')
        ]);
        DB::table('platform_keys')->insert([
            'kid' => 'ExampleKey',
            'platform_id' => 1,
            'key' => '{
                "kid": "ExampleKey",
                "alg": "RS256",
                "use": "sig",
                "key_ops": [
                    "sign",
                    "verify"
                ],
                "kty": "RSA",
                "n": "qw6ZJVIBrpKtmLPDCfMsygUVupjtFHJUUTfwvLGFcfwWrI5cG3OgVSlL9pJ9EmhdQlmlVTEbkUSdQlXscng767bdU-pe-unPU01hHVWn5vF5BC2zLiMDq3rETwPRq30E771aoP1nnXaNNzXAEUgMYszy6VnXN6-ko-ricP3h3YLLaLLM4qubzu2U8gq7yUMF1iO6j19GTjCxbsDZr_6nvlY6RCXOxpE4ev5yhdfLwjFipPaHwTFWRVrlBaHz46-dY3aWN1nDau2BEoE99grbnH4cUNURKgbzi_B0hdW1q95IU3i3mSdXxsc_ln-4udzOgkC1ovUGsw8qoTBVorhCdGH15HxhpzR4gYOTWCkJjhfvegYYEq8OyawQyLazWQfNlsNe9RSaWnr3DK_LBoQFZWheZWAojPBAsu8oDHjYCZXDEw_gmlqLTJYmXha7sHROb_aaVOeVMlg8DebiSIoFL076NX6irc4F98cNy2dtS6MuYdP4-eimPH2SLu2Dh_ZT6fhYYn4GE-e1SitcarboqcrpmhMt-WgM8kKI7KVIB6jaWp4jYaYxRap59_6Z3kZg1Dpmfjcji29SITUMt09NmY8oOLvcg0rqu-I2aXle--D6MDO3oVeoz7sAwKRYf-UprfyqaCUkMOhdBRRfuLmlUjT5DpS4MBMVkc4StPp-xPk",
                "e": "AQAB",
                "d": "Rja-zB_mStcwZK7dDzd_GgmOSsK9BhiL3a69pWak5Q3Z7MierDIBDRPfd33X0vd92F9dyyNWMoHcnz8PyEjsJ6wvG2PnysywanwnsdfYDKbvyrRLiVOcDQZekCR8Kw0tOo8aJVz-7BaejpwEk9NkKiYAS6KyAcyaIyKmAkVkMf2fKwHBsgVI9l51NgkL3egh_C08Rd-Qx0Wbf1-vu1snVaylTBXM05sGeQ36yk-y9qo42IfNX5K129Ack_xPVRVf4FTsAhpZaYnF4meknFsGnCzzyIY8_mhODhIMcmcvpDWgsIqrqjUJBbIBuvAATmKBbdvL8vDq6BMAp5tke19w_xLsvTchmsf3bWSkreHZh96gwfPhsrh8pZyp_5xwcfEn1IzloOpINcWtoraX8lNZzV-85YAL1b1w5itQcNFuP4JeKbdOe7O4HKDDQ-qVQIYsKWD-ddkOeC9-fJLx5kd-oI3ve60X09dtZS5kRNR5OG3D6l4Oii64sdrABmpfIucDcgBmvfN9gOUCusne2aC35iKZMLwte7gjm33zJn9kDzNfByyLltqQDkALtV1PvkzhR7I9pvEpF-97WHyDi3YrPZEraTQxGG8b-HVe-t_p4igpzgI7O954ryLG8qyyeoltYFNMiXSFmrHq71cLKaq18-VwFIOFcyueSkty1RAUrwE",
                "p": "05oEaeIYn_XUAmLIpNaG8nmrIuFttLjDizX9LFlt51oXUBCVJqkBmxjJ8fwMXSB_Ce82SBhLeeqyY3_IGYLTeR0Rfr10w6ivpy1JT4yWHlVThQlGkQ6nLK438OCEzr4cPnfeMVR4Lh17I6ubuKKGL0Y35nS49UV--m5mn-p1yA8ce-b-SmcMQSC4DdmX7EsyWPNLiQamSDSsLnvRjfWoon6ALJ3VN-amIEguhOlBHhj_8UE46MYUicFvsoi1IDkxxm4Gbw0VDXNFY8zwIuOfaV4sV_KDVGJRJdHTe_KBvmIOLO-fpXHhu9JF2vBMNnQ5kyYkkcnW47emWqDPv4iJIQ",
                "q": "zvLD_ZyN3tdD8FcbP_QW2zn7kgN4Gz168sojiMCAWOn6wbW5bou9hU527VO2Wrz2V3wa3Nvjnl_w4Spm-aPnITDeRpf4Qs9PfYxegLuQRKpxiZ20bB_tH13fYzjJP08HmfabNJFRz0m1up5FQkRQvAwYqlT27gpV7BtF8ayMZerOYdGNEBBPJHAf_Twj-tHuh0G2HMUzzNIjoh1zYP4DLZ8GMbxUnafFcUzPQtiTrFCULbACGm8UDd-3ubD5QDFngRQpJilTa8dsboZmw0telFSJM5qT_XcR3QCpumgc2ioN8pIEc5AFC8VnSKM72NBxMBMrTC3OdtSZaaud7MmI2Q",
                "dp": "F_XfvBGjEjHm8OI4sBmurDREwa1fG9C0K8ZQRb5WqJTWAPXvvbHzgZl7_I-64qy0Ve6laeJ-YW7HBmdIxELBKXy2J3DSkPk_8qE5JO3ZbdPhojXbrL9JBNExenAh-bsjPZubKGDVPvuNnwtmlbCpmx7LY6gh7XwSFc98hW0qKx4y7oDImKo0hAdtb_wMrPAS4mmiSwDawWBdguH4Z6fCkavbXbH9m9fdRdiGk_xJqFvSV4I4LHuJhdPGjyPVxrFTRpHc3qRPlvdPiy6AnqLBwU-tm8PVRS_g2nR5Cxb5xBIcYtA7dqN_mSIFUPZW2u-sOVJfX7bIQ37fMnH1NE4EoQ",
                "dq": "vU_3WplMbv4deE827losTn_MUtY5mTG0t0WTHBZ9Utk4OtwytZ2_0aLlyFU8C-WwtHcjW8cLgmXhxRk0-kmltgcuTwXWZcefwAoYqUrrvuTs2CccVY0fLgKByBK6ht-U5JNjh8MoQ6f6Rq1iTiyQbnXiWm8_NKLf1w1b_qkKBnG2OmWn1XoV7A9mNBJAF8LToYfLQKZlrIjPcA3g7mlwHtvKntTZ0x8Wz36kW1IkRQ2xf9nzEptqjQaK26O5X6_SL5Jx0icb2QyldLYea6kW7DopCLvvhX7XKITiv1NekXTHLY_F4rFs7Ee5JAmvRbfo00G16go9RRdXAAOuhF4YgQ",
                "qi": "VOCDC2N6bI4Q2qXDOZTYmZZSOoRA5WuKSezI72P7apUQA6yIBXf4aCWSStN2kjpwb_qUEi2yRlU-BereHAaWc0eC3LxJXpxfp9tCxHKUpX26wvC4553oWiEJhOuiiB5XRgIpcCrbx7J89Gj-D6XX9nCEA1J4QGUffJw7boHsVUpx6LkwXh3-7VzVWV9TGi2ilcXxN7ZFyWh4wk123mnnsfoc33aZySSTEmgLIxx15viEi8UoowVK2Lu089yT9V5fUlIdhVXDQI3xhaSqXeCy5B99RK4UwxzjIJeiuHj_RMraoX1lPYz4mrrBNxln5T2rB7Anzajo31jv4z2fTiDG9w"
            }'
        ]);

        // insert the reference implementation platform
        DB::table('platforms')->insert([
            'name' => 'Reference Implementation',
            'iss' => 'https://lti-ri.imsglobal.org',
            'auth_req_url' => 'https://lti-ri.imsglobal.org/platforms/643/authorizations/new',
            'jwks_url' => 'https://lti-ri.imsglobal.org/platforms/643/platform_keys/656.json'
        ]);
        // this is the shim's client_id on the RI platform
        DB::table('platform_clients')->insert([
            'platform_id' => 2,
            'client_id' => 'StrawberryCat'
        ]);
        // this is the RI platform's public key
        DB::table('platform_keys')->insert([
            'kid' => 'FakeKeyID',
            'platform_id' => 2,
            'key' => '{
                "kid": "FakeKeyID",
                "alg": "RS256",
                "use": "sig",
                "key_ops": [
                    "sign",
                    "verify"
                ],
                "kty": "RSA",
                "n": "r31rRY8F0DmnCOZqB9vGFeQvKSpX0MYgl83Utjh2KWtZHdpJ2NeUcMrHj6Hf8CQCn7sQfKXnG_tLJ0nWg8HQt84LPB21i3rMKQ0r1Gt73fWcILS42e4iz3dB9wzRhNLwpbzS6bm75DinArmbi8KMA_pT8ztj7dDayf7tw4xqvIoj8K3wTkR4Nk73f60DXgRMiCeEPz31NPGITFBdNVPxUT7s_oDHwqKbpf_x5Isn6HpnFUhtChO_CtQJjMxkrUxb5V-TaRSVF6DT-uF8AzbwyLtabiJ3_mBNp0kShqTWtr2t7Hn_XgR5Xfr1Tjk1scFfXdu3JaIT32F4faKwOzTgl1ApKxV17N4DA1glUvzKUV2uBn9DYekVfZZSD4qMFm21tHA0IY5e2fiJpsDZB7wAJ7aDtfd1U4blnfHKGNNobnt5sG0NLywGA-YcYaajxTEp2pj1ZoZ9tyQzg3CTD6VU0v2s7W_C9pRTc_3RS0RCP_fHMGeuQvNgrvV5Cb1zPbsDM3ShZtj8On6HEeAu-cErI68xP0Ghw1R0G8OC25pfwZF1mAdUrZ7653p--vW4uStPBRuAzHB81Mbv-gH_b-UvY92d1v0J7CMjd6hFqvQgx0lgacdNshwH_KMyS7nOtpQ-xJI4whDDL8fMYxGut7Tq04OSRoetyRIRF2PDC1zsoHs",
                "e": "AQAB",
                "d": "oddwzr2QBvCkczjMIGM204mVVMmQIyIVfOp-eW6oypoNndJquGhYpYzdbkFDXRxYrya9lbcK5Gtka99Uzj44VsrW4aIkg3XEQze6bDSUD6crTZ5du946jErXsxdHQs-mz7LMcHCyL09v9rtmEAZjSmfadD8ZcYFjxVYtwGIHLcnYS2aecBAFLVC2QDE3IcWlLwwCkrB5K1J0-KxEyaGYs-zsc-ogUAfWs8jPNI6BR41a8-3PYbPERCA4jnkRgCEjvu3d7NLmjNvtoznAPNa2SOlABMIVF6uI8fYZ98KdofpcI_J-6B4IL_v5ryJjuoMziEPRLAvFeAVUMcmP7YpedKamqxU1dfIX5VsYmfwJMsBNfL74FO5GJ7ZSONxDq9jxwA_BKf0eQM_L1m123WwLRDMYVw2zF9HDvYKHdtlkh9bYcv7we3qxQvtB5LEohiSp9MYYnC3YTvwDo2-ZF04AtyCrDcofwJrSjbh6PaCqDe4t1I0JbSIS4du6yp_3pfciC6mifQY9VdSX2ihodv6lYVL2BzA9LaX60oaP7vZBPhFIokaitW9Nmhmu7I7iTX8sA-MlWiQt-9Y9VjyAQuxBBpAaTwkRr0oqJFkLZrfrh9YeKOn1uLixszOmD-nNlPhjvjtTVU_JwKdaJh3nQxAFGv-2B29ZmKVuz4MUn-b_9YE",
                "p": "1n6gSuhC2GLSEgQIl_rfoHFiH-H_S3o5WZXEWlT3k7K9vvYMz4ERsq5YKxJraUmbWvsitWE5LN-qvPQW1LEttmYnKAkHrRW_Ub0Qjb71k3XoekLd8GE2jBupS__e9r216gJpqTCmBT-hWU-Tbtkn7K5gjqzVjl6CLMP6jpBSJl6ZN2OAxupoJkhLGA2PZkAvII1iMD8pGUFXsy_HbfhREhvxHAjLMzRJyTRPUTT9smRQ8a1x-UBLbpdbE8p-y-h2Q-LFOh2u34qgSwmQw3GZD0M5k7Kjl1A2cXMNBte9uFfCiqFGKdYuSJcjrl7lR50-sYJ1UqyN2gQlwywujuffow",
                "q": "0XKf8-yp9MKfLwd7ky9pS6dkT7SFqNfcdoBA03QLhIfBC9hrS3QasGehsvZO6vRrxGv3oiIKRQiP7l5Ja6zYWzgFuzEb9ijDhuY_F2omnD7Va1J12sbjhQFA1YlM6XK7wPGlRA66cCk8WbkYDeXdJocYc2S8D3L-rDC-904-BxJyAJiDMQfGRgZlvAXEmCERzGGKs4FyVd0aVCbNQpkHTuZ_Ci2kVd-VSski29p1fm8C2PiatHdCRUxEjUTEuW8ahuvD-zyC0DaMMbGdruVUX9YaG7BTNbAq4J8St5VsPtfVVujWu-4N7Y8e9b1EFGwh9uPn9HMm9b1_MK8chtxpSQ",
                "dp": "hHJkYFzTCfLbbKx48f_DnrrksHNIxT6PszW7rVvSFK3GZOhiOK-mUgM-Bjq1gKom-CMi54VFXOQv9cVzY5qUc5DYlOwehU5sePiaBCzqT0f7aaNokEo2-IUMIRW6g_nZHsqmnAaiPZNw-kkc7NHCyW9TyEYJCIRrNWE-klGjqHW5fm4-0pVkAl-Znxygc68map9jBg-6PRIQKWFTcCBpTxlZ5Mm5T8D13vwiDi7-iO1Q3isPua1t5rgnUS005oyAmqKozp4NoZNkL3GUplq6_KWyI_Wm9oRdWeiFHriROgdW5Wt4e9T5d3F8YuvOAE6kq8-2ynoqjXkKKF5b0Xp0qQ",
                "dq": "N0qwST-f-lpqlYq_J7LrdCpjg9sfMdySecIP2wKWi80evFbMtGWZKhqhiFIXsVUHIhpyjB26YlEJVh8T-NXvY91dPfaP8CMAwxsDatX-DV9VDfj93dyFcbZL_FM7t6EvSZdBTr8onrV0FieT3w9gm2wsm0Hrl5R_AMv5jeXc4BcRe3DHqWjpAdFsh_9pz9NjFHZnnE2-9kXhTnfT1hH56O1WekRkTrsweUax6iI4xEDgGpE-ngehuhORQU33NRDMivOwkCGAUyEjT3cZLnOiG46047xfqxuvpg64bTiIE-r8ie37yi7lBGK1BKCLWWzWZovxzwmbLmJxMlP4Du4e6Q",
                "qi": "CTT0sIjA_O5L45FXiv0Hw_i2j1uK_QmjEer7uUp9wvLxwvpq8OFT5LKHCpL8XvU8cRaT4uwj4McWDvF51U8TH3GMFclG0bP--_tCix1R1oyCD7TVe-s41jQpxpYIPxT92e29Yw5yNjfTDh26laeSq3IGLledDolisqFZYE11cLXmcjT3cw0xtWRItsOjlAjGRleuTg8iBMTIVsjlcblt6wHj5dItrSRB1_aki9ndrdgRIUC4_kJnak99dKfTK39l6cSSUA4rvuOA1bf6VUyJS_ZFzhvo0nEuXeSyxvPt07h8QIdV87P-anFSm0iCYs63xpNLA9HZ3e8nLZCGBbWXSA"
                }'
        ]);


        // insert test canvas
        DB::table('platforms')->insert([
            'name' => 'UBC Test Canvas',
            'iss' => 'https://canvas.test.instructure.com',
            'auth_req_url' =>
                'https://ubc.test.instructure.com/api/lti/authorize',
            'jwks_url' => 'https://ubc.test.instructure.com/api/lti/security/jwks'
        ]);
        // this is the shim's client_id on test canvas
        DB::table('platform_clients')->insert([
            'platform_id' => 3,
            'client_id' => '112240000000000110'
        ]);
    }
}
