<?php
namespace UBC\LTI;

use Jose\Component\Core\JWK;

// placeholder for dealing with RSA keys used for JWT signing & verification,
// just returns hardcoded keys, the real thing will have to read this from the
// database instead or otherwise retrieve it somehow
class KeyStorage
{
    // ideally given a platform iss, return the associated public key so we can
    // verify the JWTs they send to us, note that the real thing will need
    // to have some way of retrieving this from a JWKSet URL
    public static function getPlatformPublicKey(): JWK
    {
        // note that this JWK contains both the public and private keys
        // but we're returning only the public part
        $key = JWK::createFromJson('
            {
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
            }
        ');
        return $key->toPublic();
    }

    // get this component's private key used for signing JWTs we send
    public static function getMyPrivateKey(): JWK
    {
        $key = JWK::createFromJson('{
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
            }
        ');
        return $key;
    }

    // get this component's public key, we give it out to 3rd parties
    // to verify our signed JWTs
    public static function getMyPublicKey(): JWK
    {
        $key = self::getMyPrivateKey();
        return $key->toPublic();
    }
}
