import {Alert} from 'react-native';
import {ApiError} from '../api/client';
import {NativeWriteUnavailableError} from '../api/portfolio';
import {openWebFlow} from './openWebFlow';

const WEB_FALLBACK_STATUSES = new Set([404, 405, 501]);

export const showFinancialFlowError = (
  error: unknown,
  label: string,
  webPath: string,
) => {
  const shouldUseWeb =
    error instanceof NativeWriteUnavailableError ||
    (error instanceof ApiError && error.status !== undefined && WEB_FALLBACK_STATUSES.has(error.status));

  if (shouldUseWeb) {
    Alert.alert(
      `Continue ${label.toLowerCase()} securely`,
      `The native ${label.toLowerCase()} API is not enabled yet. Complete this request on the COFCO Capital website.`,
      [
        {text: 'Cancel', style: 'cancel'},
        {
          text: 'Open website',
          onPress: () => {
            void openWebFlow(webPath, label);
          },
        },
      ],
    );
    return;
  }

  Alert.alert(`${label} failed`, error instanceof Error ? error.message : `Unable to complete ${label.toLowerCase()}.`);
};
