/**
 * Feedback Components
 * NoData and NoInternet indicators
 */

import React from 'react';
import { View, Text, Image, StyleSheet, TouchableOpacity } from 'react-native';
import { colors, fontSize, spacing } from '../constants/theme';

interface NoDataProps {
    message?: string;
}

export function NoData({ message = 'No Data Found' }: NoDataProps) {
    return (
        <View style={styles.container}>
            <Image
                source={require('../assets/images/nodata.png')}
                style={styles.image}
                resizeMode="contain"
            />
            <Text style={styles.message}>{message}</Text>
        </View>
    );
}

interface NoInternetProps {
    onRetry: () => void;
}

export function NoInternet({ onRetry }: NoInternetProps) {
    return (
        <View style={styles.container}>
            <Image
                source={require('../assets/images/nointernet.png')}
                style={styles.image}
                resizeMode="contain"
            />
            <Text style={styles.title}>No Internet Connection</Text>
            <Text style={styles.subtitle}>Please check your internet connection and try again.</Text>
            <TouchableOpacity style={styles.retryButton} onPress={onRetry}>
                <Text style={styles.retryButtonText}>Try Again</Text>
            </TouchableOpacity>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        padding: spacing.xl,
    },
    image: {
        width: 200,
        height: 200,
        marginBottom: spacing.lg,
    },
    message: {
        fontSize: fontSize.lg,
        color: colors.gray,
        textAlign: 'center',
    },
    title: {
        fontSize: fontSize.xl,
        fontWeight: 'bold',
        color: colors.black,
        marginBottom: spacing.sm,
    },
    subtitle: {
        fontSize: fontSize.md,
        color: colors.gray,
        textAlign: 'center',
        marginBottom: spacing.xl,
    },
    retryButton: {
        backgroundColor: colors.orange,
        paddingHorizontal: spacing.xl,
        paddingVertical: spacing.md,
        borderRadius: spacing.md,
    },
    retryButtonText: {
        color: colors.white,
        fontSize: fontSize.md,
        fontWeight: 'bold',
    },
});
