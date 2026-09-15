import React from 'react';
import {Screen} from '../../components/Screen';
import {Surface} from '../../components/Surface';
import {Typography} from '../../components/Typography';
import {PrimaryButton} from '../../components/PrimaryButton';
import {useTheme} from '../../theme';
import {openWebFlow} from '../../lib/openWebFlow';

const ForgotPasswordScreen = () => {
  const theme = useTheme();
  const handleSubmit = () => openWebFlow('/wsi/forgot-password/', 'Password reset');

  return (
    <Screen requireAuth={false}>
      <Surface>
        <Typography variant="subtitle" weight="medium">
          Reset password
        </Typography>
        <Typography variant="caption" style={{color: theme.palette.muted, marginTop: 6}}>
          Continue to the secure COFCO Capital page to request a reset link.
        </Typography>

        <PrimaryButton label="Open password reset" fullWidth style={{marginTop: 16}} onPress={handleSubmit} />
      </Surface>
    </Screen>
  );
};

export default ForgotPasswordScreen;
