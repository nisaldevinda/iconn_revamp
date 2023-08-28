import React, { useState, useEffect } from 'react';
import ResetAndForgotPasswordForm from './ResetAndForgotPasswordForm';
import { checkVerficationToken } from '@/services/login';
import { useParams } from 'umi';
import _ from 'lodash';
import { history } from 'umi';


export type FormRouteProps = {
    type: any;
    id: string;
    verificationToken: string;
};

const LoginOptions: React.FC = () => {
    const { type, id, verificationToken } = useParams<FormRouteProps>();
    const tokenObject = {type,verificationToken}
    const [isTokenValid, setIsTokenValid] = useState<boolean>();
   
    useEffect(() => {

        checkVerficationToken(tokenObject).then((result: any) => {
            if (_.isBoolean(result.data)) {
                console.log(result);
                setIsTokenValid(result.data);
            }

        }).catch((error) => {
        
            if (_.isBoolean(error.data)) {
                setIsTokenValid(error.data);
            }

        })
    })

    if (!_.isUndefined(isTokenValid) && _.isBoolean(isTokenValid)) {

        if (_.isBoolean(isTokenValid) && isTokenValid) {
            return <ResetAndForgotPasswordForm type={type} userId={id} verficationToken={verificationToken} />;
        }
     
        if(_.isBoolean(isTokenValid) && isTokenValid === false){
            history.push('/auth/password-options/not-found');
        }
    }

    return <></>

};

export default LoginOptions;
