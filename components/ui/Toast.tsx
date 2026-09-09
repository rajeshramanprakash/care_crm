/**
 * Toast Component
 * Simple toast notification component
 */

import React, { useEffect, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    Animated,
    TouchableOpacity,
    Dimensions,
    Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { colors, fontSize, spacing, borderRadius, shadows } from '../../constants/theme';

interface ToastProps {
    visible: boolean;
    message: string;
    type?: 'success' | 'error' | 'info';
    onDismiss: () => void;
    duration?: number;
}

export default function Toast({
    visible,
    message,
    type = 'info',
    onDismiss,
    duration = 3000
}: ToastProps) {
    const [fadeAnim] = useState(new Animated.Value(0));

    useEffect(() => {
        if (visible) {
            Animated.timing(fadeAnim, {
                toValue: 1,
                duration: 300,
                useNativeDriver: true,
            }).start();

            const timer = setTimeout(() => {
                handleDismiss();
            }, duration);

            return () => clearTimeout(timer);
        } else {
            Animated.timing(fadeAnim, {
                toValue: 0,
                duration: 300,
                useNativeDriver: true,
            }).start();
        }
    }, [visible]);

    const handleDismiss = () => {
        Animated.timing(fadeAnim, {
            toValue: 0,
            duration: 300,
            useNativeDriver: true,
        }).start(() => {
            onDismiss();
        });
    };

    if (!visible) return null;

    const bgColors = {
        success: colors.success,
        error: colors.delete,
        info: colors.blackMedium,
    };

    const icons: Record<string, keyof typeof Ionicons.glyphMap> = {
        success: 'checkmark-circle-outline',
        error: 'alert-circle-outline',
        info: 'information-circle-outline',
    };

    return (
        <Animated.View
            style={[
                styles.container,
                {
                    opacity: fadeAnim,
                    transform: [
                        {
                            translateY: fadeAnim.interpolate({
                                inputRange: [0, 1],
                                outputRange: [-20, 0],
                            }),
                        },
                    ],
                },
            ]}
        >
            <View style={[styles.card, { backgroundColor: bgColors[type] }]}>
                <Ionicons name={icons[type]} size={24} color={colors.white} style={styles.icon} />
                <Text style={styles.message}>{message}</Text>
                <TouchableOpacity onPress={handleDismiss} style={styles.closeButton}>
                    <Ionicons name="close" size={20} color={colors.white} />
                </TouchableOpacity>
            </View>
        </Animated.View>
    );
}

const styles = StyleSheet.create({
    container: {
        position: 'absolute',
        top: Platform.OS === 'ios' ? 60 : 40,
        left: 20,
        right: 20,
        zIndex: 9999,
    },
    card: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: spacing.md,
        borderRadius: borderRadius.md,
        ...shadows.md,
    },
    icon: {
        marginRight: spacing.md,
    },
    message: {
        flex: 1,
        color: colors.white,
        fontSize: fontSize.md,
        fontWeight: '600',
    },
    closeButton: {
        padding: spacing.xs,
        marginLeft: spacing.sm,
    },
});
