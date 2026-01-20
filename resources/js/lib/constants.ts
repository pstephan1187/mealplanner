/**
 * Shared constants for the meal planner application.
 */

export const MEAL_TYPES = ['Breakfast', 'Lunch', 'Dinner'] as const;

export type MealType = (typeof MEAL_TYPES)[number];
