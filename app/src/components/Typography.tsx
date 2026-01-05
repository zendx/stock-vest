import React from 'react';
import {Text, TextProps} from 'react-native';
import {useTheme} from '../theme';

type TypographyProps = TextProps & {
  variant?: 'title' | 'subtitle' | 'body' | 'caption';
  weight?: 'regular' | 'medium' | 'bold';
};

export const Typography = ({
  children,
  variant = 'body',
  weight = 'regular',
  style,
  ...rest
}: TypographyProps) => {
  const theme = useTheme();
  const sizes = {
    title: 28,
    subtitle: 18,
    body: 15,
    caption: 13,
  };
  const lineHeights = {
    title: 34,
    subtitle: 24,
    body: 22,
    caption: 18,
  };
  const fontFamilies = variant === 'title' ? theme.typography.title : theme.typography.body;

  return (
    <Text
      style={[
        {
          color: theme.palette.text,
          fontSize: sizes[variant],
          lineHeight: lineHeights[variant],
          fontFamily: fontFamilies[weight],
        },
        style,
      ]}
      {...rest}
    >
      {children}
    </Text>
  );
};
